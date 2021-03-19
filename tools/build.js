const sass = require('node-sass'),
    color = require('chalk'),
    fs = require('fs'),
    SpriteMagicImporter = require('sprite-magic-importer'),
    UglifyJS = require("uglify-js"),
    srcDir = __dirname + '/../admin/src',
    distDir = __dirname + '/../admin',
    action = process.argv.pop();

let spriteImporter = SpriteMagicImporter({
    sass_dir: srcDir,
    images_dir: srcDir + '/img',
    generated_images_dir: distDir + '/img',
    http_stylesheets_path: '.',
    http_generated_images_path: 'img',
    use_cache: false,
    cache_dir: __dirname + '/tmp',

    // spritesmith options
    spritesmith: {
        algorithm: 'top-down',
        padding: 0
    },

    // imagemin-pngquant options
    pngquant: {
        quality: 75,
        speed: 10
    }
});

function buildSass(file) {
    let outFile = distDir + '/css/' + file.split('.')[0] + '.css',
        sassDir = srcDir + '/scss';
    console.log(color.green('processing ' + file));

    sass.render({
        file: sassDir + '/' + file,
        outFile: outFile,
        includePaths: [sassDir],
        outputStyle: 'compact',
        importer: spriteImporter
    }, function (error, result) {
        if (error) {
            console.error(color.red('Error: ' + error.message));
            console.error(color.red('File: ' + error.file + ' [Line:' + error.line + ']'
                + '[Col:' + error.column + ']'));
        } else {
            fs.writeFileSync(outFile, result.css.toString());
        }
    });
}

function minifyJs(file) {
    console.log(color.green('minify ' + file));
    let code = {};
    code[file] = fs.readFileSync(srcDir + '/js/' + file).toString('utf8');

    fs.writeFileSync(distDir + '/js/' + file,
        UglifyJS.minify(code).code);
}

function listFiles(dir, regExp) {
    let files = fs.readdirSync(srcDir + dir), result = [];

    files.map(function (file) {
        if (file.match(regExp)) {
            result.push(file);
        }
    });

    return result;
}

if (action === 'css') {
    console.log(color.blue('build css'));

    listFiles('/scss', /^[a-z0-9-]+\.scss$/).forEach(function (file) {
        buildSass(file);
    });
} else if (action === 'js') {
    console.log(color.blue('build js'));

    listFiles('/js', /^[-\w]+\.js$/).forEach(function (file) {
        minifyJs(file);
    });
} else {
    console.log(color.red('Please choose correct action.'));
}
