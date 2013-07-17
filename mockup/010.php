<?php include 'slice/header.inc.html'; ?>
<div class="typecho-popup">
    <div class="typecho-popup-content">
        <div class="typecho-view-comment">
            <ul>
                <li><label>Name</label><input type="text" value="admin" /></li>
                <li><label>Email</label><input type="text" value="admin@admin.com" /></li>
                <li><label>Name</label><input type="text" value="http://www.typecho.net" /></li>
            </ul>
            <h4>Mesage</h4>
            <textarea cols="30" rows="10"></textarea>
            <p class="status">
            Comment at <em>10 Mar 2009 4:53:23AM</em> form IP <a href="#">127.0.0.1</a> <a href="#" class="ban">Ban?</a>
            </p>
        </div>
        <div class="submit">
            <button>保存修改</button>
            <button>关闭</button>
        </div>
    </div>
</div>
<?php include 'slice/typecho-head-guid.inc.html'; ?>
<div class="main">
    <div class="body body-950">
        <?php include 'slice/typecho-page-title.inc.html'; ?>
        <div class="container typecho-page-main">
            <?php include 'slice/typecho-list-table.inc.html'; ?>
        </div>
    </div>
</div>
<?php include 'slice/typecho-foot.inc.html'; ?>
<?php include 'slice/footer.inc.html'; ?>
