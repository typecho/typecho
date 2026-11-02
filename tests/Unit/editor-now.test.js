// 无浏览器/网络依赖；直接执行页面中的真实控制器与 PHP 生成的配置。
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
if (!process.env.TYPECHO_EDITOR_CLOCK_CHILD) {
    for (const zone of ['UTC', 'Asia/Shanghai', 'Europe/Berlin', 'America/Los_Angeles']) {
        console.log('BROWSER TZ=' + zone);
        execFileSync(process.execPath, [__filename], {stdio: 'inherit',
            env: {...process.env, TZ: zone, TYPECHO_EDITOR_CLOCK_CHILD: '1'}});
    }
    process.exit(0);
}
const root = path.resolve(__dirname, '../..');
const source = fs.readFileSync(path.join(root, 'admin/write-js.php'), 'utf8');
const begin = source.indexOf('(function (root) {');
const end = source.indexOf('// <<< typecho-editor-now end', begin);
assert.ok(begin >= 0 && end > begin, 'the production editor controller must be present');
const code = source.slice(begin, end);
const sandbox = {Date, isFinite};
vm.runInNewContext(code, sandbox);
const api = sandbox.TypechoEditorNow;
const config = (zone, epoch) => JSON.parse(execFileSync('php',
    [path.join(__dirname, '../fixtures/editor-config.php'), zone, String(epoch)], {encoding: 'utf8'}));
let count = 0;
function check(name, action) { action(); count++; console.log('PASS ' + name); }
function fixture(clock) {
    const input = {id: 'date', value: '2020-01-01 04:05'};
    const other = {id: 'other'};
    const listeners = {};
    const timepicker = {};
    const inst = {};
    const seen = [];
    let elapsed = 0;
    let delegated = 0;
    let created;
    function jquery(value) {
        const element = value === '#date' ? input : value === '#other' ? other : value;
        return {0: element, on(events, callback) {
            for (const event of events.split(' ')) (listeners[event] ||= []).push(callback);
        }, trigger(event) { for (const callback of listeners[event] || []) callback(); }};
    }
    const picker = {_gotoToday() { delegated++; return 'original'; }, _getInst() { return inst; },
        _get() { return timepicker; }, _updateDatepicker() {
            // 真实库在此重新读取 input；旧值不能在消费者回调中再次出现。
            seen.push({text: input.value, created: created.value, disabled: created.disabled});
        }};
    const controller = api.create({jquery, datepicker: picker, config: clock, input,
        document: {createElement() { return {}; }}, form: {appendChild(e) { created = e; }},
        performanceNow: () => elapsed, anchorPerf: 0});
    controller.install();
    return {input, other, inst, timepicker, picker, controller, seen,
        change(text, event = 'input') { input.value = text; jquery(input).trigger(event); },
        tick(ms) { elapsed = ms; }, get delegated() { return delegated; }};
}
const first = Date.parse('2021-11-07T08:10:00Z') / 1000;
const second = Date.parse('2021-11-07T09:10:00Z') / 1000;
check('two repeated wall clocks keep distinct UTC instants', () => {
    const a = fixture(config('America/Los_Angeles', first));
    const b = fixture(config('America/Los_Angeles', second));
    a.picker._gotoToday('#date'); b.picker._gotoToday('#date');
    assert.equal(a.input.value, '2021-11-07 01:10');
    assert.equal(a.input.value, b.input.value);
    assert.equal(a.controller.createdElement.value, String(first));
    assert.equal(b.controller.createdElement.value, String(second));
    assert.equal(b.timepicker.hour, 1); assert.equal(b.timepicker.minute, 10);
    assert.equal(b.seen[0].text, b.input.value);
    assert.equal(b.seen[0].created, String(second));
    assert.equal(b.seen[0].disabled, false);
});
check('cursor movement keeps the exact instant but editing clears it', () => {
    const f = fixture(config('America/Los_Angeles', second));
    f.picker._gotoToday('#date');
    f.change(f.input.value, 'keyup');
    assert.equal(f.controller.createdElement.value, String(second));
    assert.equal(f.controller.createdElement.disabled, false);
    f.change('2021-11-07 01:11', 'change');
    assert.equal(f.controller.createdElement.disabled, true);
    f.change('2021-11-07 01:10');
    assert.equal(f.controller.createdElement.disabled, true);
});
check('page age follows the server clock across a DST switch', () => {
    const anchor = Date.parse('2021-03-14T09:59:00Z') / 1000;
    const f = fixture(config('America/Los_Angeles', anchor));
    f.tick(120000); f.picker._gotoToday('#date');
    assert.equal(f.input.value, '2021-03-14 03:01');
    assert.equal(f.controller.createdElement.value, String(anchor + 120));
});
check('PHP fixed aliases and quarter-hour offsets keep their semantics', () => {
    for (const [zone, expected] of [['CEST', '2021-11-07 11:10'], ['CST', '2021-11-07 03:10'],
        ['+05:45', '2021-11-07 14:55'], ['20700', '2021-11-07 14:55'], ['12345', '2021-11-07 12:35']]) {
        const f = fixture(config(zone, second)); f.picker._gotoToday('#date');
        assert.equal(f.input.value, expected, zone);
    }
});
check('other fields keep their existing handler and manual input keeps its text', () => {
    const f = fixture(config('America/Los_Angeles', second));
    assert.equal(f.picker._gotoToday('#other'), 'original');
    assert.equal(f.delegated, 1);
    f.change('2021-07-04 12:34');
    assert.equal(f.input.value, '2021-07-04 12:34');
    assert.equal(f.controller.createdElement.disabled, true);
});
check('autosave validates the UTC marker before FormData takes its snapshot', () => {
    const f = fixture(config('America/Los_Angeles', second));
    f.picker._gotoToday('#date');
    f.input.value = '2021-11-07 01:11'; // 程序赋值，没有 input/change 事件。
    const begin = source.indexOf('Typecho.savePost = function(cb) {');
    const end = source.indexOf('\n    };', begin) + '\n    };'.length;
    assert.ok(begin >= 0 && end > begin);
    const callbackCode = source.slice(begin, end).replace(/<\?php[\s\S]*?\?>/g, '');
    let posted;
    const context = {Typecho: {}, changed: true, window: {typechoEditorNow: f.controller},
        autoSave: {text() { return this; }},
        form: {get() { return {}; }, attr() { return '/save'; },
            triggerHandler() { f.change(f.input.value, 'submit'); }},
        FormData: class {
            constructor() { if (!f.controller.createdElement.disabled) this.created = f.controller.createdElement.value; }
            append() {}
        }, $: {ajax(options) { posted = options.data; }}};
    vm.runInNewContext(callbackCode, context);
    context.Typecho.savePost();
    assert.ok(posted);
    assert.equal(posted.created, undefined);
});
console.log('OK (' + count + ' editor callback integration cases)');
