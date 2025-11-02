<?php
/**
 * 归档模板
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__'))
  exit; ?>
<?php $this->need('header.php'); ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('css/archive.css'); ?>" />

<main class="main-container">
  <div class="content-wrapper">
    <div class="main-content">
      <section class="posts-list-section">
        <div class="archive-container card">
          <h2 class="section-title">归档
            <button class="layout-toggle" type="button" aria-label="切换布局" title="切换布局" style="float:right;">
              <img class="layout-toggle-icon" src="<?php $this->options->themeUrl('img/expand.svg'); ?>"
                alt="toggle layout">
            </button>
          </h2>
          <?php
          // ---------------- 参数读取 ----------------
          $nowYear = (int) date('Y');
          $selectedYear = (int) $this->request->get('year');

          $categorySlug = trim((string) ($this->request->get('cat') ?: $this->request->get('category')));
          $tagSlug = trim((string) ($this->request->get('tg') ?: $this->request->get('tag')));

          // ---------------- 收集所有文章的基本信息（cid、created） ----------------
          
          $db = Typecho_Db::get();
          $prefix = $db->getPrefix();
          $rows = $db->fetchAll(
            $db->select('cid', 'created', 'title', 'slug', 'commentsNum')
              ->from('table.contents')
              ->where('type = ?', 'post')
              ->where('status = ?', 'publish')
              ->where('created < ?', $this->options->time)
              ->order('created', Typecho_Db::SORT_DESC)
          );
          $postsData = [];

          foreach ($rows as $r) {
            $postsData[] = [
              'cid' => (int) $r['cid'],
              'created' => (int) $r['created'],
              'title' => (string) $r['title'],
              'slug' => (string) $r['slug'],
              'commentsNum' => (int) $r['commentsNum'],
            ];
          }

          // ---------------- 先按“年份条件”预过滤获得候选 cid ----------------
          $eligibleCids = [];
          foreach ($postsData as $p) {
            $y = (int) date('Y', $p['created']);
            if ($selectedYear) {
              if ($y === $selectedYear)
                $eligibleCids[] = $p['cid'];
            } else {
              $eligibleCids[] = $p['cid'];
            }
          }

          // ---------------- 若传入 cat/tg，再通过关系表做二次过滤 ----------------
          $cidAfterYear = $eligibleCids;
          $cidAfterCatTag = $cidAfterYear;

          if (($categorySlug || $tagSlug) && !empty($cidAfterYear)) {
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            // 只查候选文章的分类/标签关联
            $rows = $db->fetchAll(
              $db->select('table.relationships.cid', 'table.metas.slug', 'table.metas.type')
                ->from('table.relationships')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid IN ?', $cidAfterYear)
                ->where('table.metas.type IN ?', ['category', 'tag'])
            );

            $postCats = [];
            $postTags = [];
            foreach ($rows as $r) {
              $cid = (int) $r['cid'];
              if ($r['type'] === 'category') {
                if (!isset($postCats[$cid]))
                  $postCats[$cid] = [];
                $postCats[$cid][] = $r['slug'];
              } elseif ($r['type'] === 'tag') {
                if (!isset($postTags[$cid]))
                  $postTags[$cid] = [];
                $postTags[$cid][] = $r['slug'];
              }
            }

            // 应用分类筛选
            if ($categorySlug) {
              $tmp = [];
              foreach ($cidAfterCatTag as $cid) {
                if (!empty($postCats[$cid]) && in_array($categorySlug, $postCats[$cid])) {
                  $tmp[] = $cid;
                }
              }
              $cidAfterCatTag = $tmp;
            }
            // 应用标签筛选
            if ($tagSlug) {
              $tmp = [];
              foreach ($cidAfterCatTag as $cid) {
                if (!empty($postTags[$cid]) && in_array($tagSlug, $postTags[$cid])) {
                  $tmp[] = $cid;
                }
              }
              $cidAfterCatTag = $tmp;
            }
          }

          $allowedCidSet = [];
          foreach ($cidAfterCatTag as $cid) {
            $allowedCidSet[$cid] = true;
          }

          // ---------------- 统计（基于最终 allowedCidSet） ----------------
          $yearCounts = [];
          $monthCounts = [];
          $totalCount = 0;
          foreach ($postsData as $p) {
            if (!isset($allowedCidSet[$p['cid']]))
              continue;
            $y = (int) date('Y', $p['created']);
            $m = (int) date('n', $p['created']);
            $yearCounts[$y] = isset($yearCounts[$y]) ? $yearCounts[$y] + 1 : 1;
            if (!isset($monthCounts[$y]))
              $monthCounts[$y] = [];
            $monthCounts[$y][$m] = isset($monthCounts[$y][$m]) ? $monthCounts[$y][$m] + 1 : 1;
            $totalCount++;
          }

          // ---------------- 可选年份列表（受分类/标签筛选影响，但不受年份筛选影响） ----------------
          $yearsForBar = [];
          if (!empty($cidAfterYear)) {
            $base = ($categorySlug || $tagSlug) ? $cidAfterCatTag : $cidAfterYear;
            $baseSet = [];
            foreach ($base as $cid)
              $baseSet[$cid] = true;
            foreach ($postsData as $p) {
              if (!isset($baseSet[$p['cid']]))
                continue;
              $yy = (int) date('Y', $p['created']);
              $yearsForBar[$yy] = true;
            }
          }
          $yearsForBar = array_keys($yearsForBar);
          rsort($yearsForBar);

          // ---------------- 构造筛选条链接工具 ----------------
          function build_query_keep($overrides = [], $removes = [])
          {
            $params = $_GET;
            foreach ($overrides as $k => $v) {
              if ($v === '' || $v === null) {
                unset($params[$k]);
              } else {
                $params[$k] = $v;
              }
            }
            foreach ($removes as $k) {
              unset($params[$k]);
            }
            foreach ($params as $k => $v) {
              if ($v === '' || $v === null)
                unset($params[$k]);
            }
            return http_build_query($params);
          }
          ?>

          <!-- 横向筛选条：日期 / 分类 / 标签 -->
          <div class="filter-bar">
            <div class="filter-row">
              <div class="filter-title">日期</div>
              <div class="filter-options">
                <?php $qs = build_query_keep([], ['year']); ?>
                <a class="filter-chip <?php if (!$selectedYear)
                  echo 'active'; ?>"
                  href="<?php $this->permalink(); ?><?php echo $qs ? ('?' . $qs) : ''; ?>">全部</a>
                <?php foreach ($yearsForBar as $yy): ?>
                  <?php $qs = build_query_keep(['year' => $yy], []); ?>
                  <a class="filter-chip <?php if ($selectedYear === (int) $yy)
                    echo 'active'; ?>"
                    href="<?php $this->permalink(); ?>?<?php echo $qs; ?>"><?php echo $yy; ?></a>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="filter-row">
              <div class="filter-title">分类</div>
              <div class="filter-options">
                <?php $qs = build_query_keep([], ['cat', 'category']); ?>
                <a class="filter-chip <?php if (!$categorySlug)
                  echo 'active'; ?>"
                  href="<?php $this->permalink(); ?><?php echo $qs ? ('?' . $qs) : ''; ?>">全部</a>
                <?php \Widget\Metas\Category\Rows::alloc()->to($categories); ?>
                <?php while ($categories->next()): ?>
                  <?php $slug = $categories->slug;
                  $name = $categories->name; ?>
                  <?php $qs = build_query_keep(['cat' => $slug], []); ?>
                  <a class="filter-chip <?php if ($categorySlug === $slug)
                    echo 'active'; ?>"
                    href="<?php $this->permalink(); ?>?<?php echo $qs; ?>"><?php echo htmlspecialchars($name); ?></a>
                <?php endwhile; ?>
              </div>
            </div>

            <div class="filter-row">
              <div class="filter-title">标签</div>
              <div class="filter-options">
                <?php $qs = build_query_keep([], ['tg', 'tag']); ?>
                <a class="filter-chip <?php if (!$tagSlug)
                  echo 'active'; ?>"
                  href="<?php $this->permalink(); ?><?php echo $qs ? ('?' . $qs) : ''; ?>">全部</a>
                <?php \Widget\Metas\Tag\Cloud::alloc()->to($tags); ?>
                <?php while ($tags->next()): ?>
                  <?php $slug = $tags->slug;
                  $name = $tags->name; ?>
                  <?php $qs = build_query_keep(['tg' => $slug], []); ?>
                  <a class="filter-chip <?php if ($tagSlug === $slug)
                    echo 'active'; ?>"
                    href="<?php $this->permalink(); ?>?<?php echo $qs; ?>"><?php echo htmlspecialchars($name); ?></a>
                <?php endwhile; ?>
              </div>
            </div>
          </div>

          <?php if ($totalCount === 0): ?>
            <p class="no-content">没有找到内容</p>
          <?php else: ?>
            <div class="archive-summary" style="margin-bottom:12px;">
              <span>
                共 <?php echo $totalCount; ?> 篇文章
                <?php if ($selectedYear): ?>（年份：<?php echo $selectedYear; ?>）<?php endif; ?>
                <?php if ($categorySlug): ?>，分类：<?php echo htmlspecialchars($categorySlug); ?><?php endif; ?>
                <?php if ($tagSlug): ?>，标签：<?php echo htmlspecialchars($tagSlug); ?><?php endif; ?>
              </span>
            </div>

            <?php
            // -------------- 渲染归档列表（分年/月） --------------
            $year = 0;
            $month = 0;
            // 直接使用预取的 postsData 遍历 (已按时间倒序)
            foreach ($postsData as $pRow) {
              $cid = $pRow['cid'];
              if (!isset($allowedCidSet[$cid]))
                continue;
              $createdTs = $pRow['created'];
              $y = (int) date('Y', $createdTs);
              $m = (int) date('n', $createdTs);

              if ($year != $y) {
                if ($year > 0) {
                  if ($month > 0) {
                    echo "</ul>";
                    $month = 0;
                  }
                  echo "</div>"; // 结束上一年块
                }
                $year = $y;
                $yc = isset($yearCounts[$year]) ? $yearCounts[$year] : 0;
                echo "<div class=\"archive-year\"><h3 class=\"archive-year-title\">{$year} 年 <span class=\"archive-count\">({$yc} 篇)</span></h3>";
              }
              if ($month != $m) {
                if ($month > 0)
                  echo "</ul>"; // 结束上个月
                $month = $m;
                $mc = isset($monthCounts[$year][$month]) ? $monthCounts[$year][$month] : 0;
                echo "<h4 class=\"archive-month-title\">{$month} 月 <span class=\"archive-count\">({$mc} 篇)</span></h4><ul class=\"archive-list\">";
              }

              // 构造文章链接 (使用 Router::url 根据当前路由设置生成); 如果无法生成则回退 #
              $rowBasic = [
                'cid' => $cid,
                'slug' => $pRow['slug'],
                'created' => $createdTs,
                'year' => date('Y', $createdTs),
                'month' => date('m', $createdTs),
                'day' => date('d', $createdTs),
              ];
              $permalink = '#';
              try {
                // post 路由名在 Typecho 默认是 'post'
                $permalink = Typecho_Router::url('post', $rowBasic, $this->options->index);
              } catch (Exception $e) {
              }

              echo '<li class="archive-item">'
                . '<span class="archive-date">' . date('m-d', $createdTs) . '</span>'
                . '<a class="archive-link" href="' . htmlspecialchars($permalink) . '">' . htmlspecialchars($pRow['title']) . '</a>'
                . '<span class="archive-comments">评论(' . (int) $pRow['commentsNum'] . ')</span>'
                . '</li>';
            }
            if ($year > 0) {
              if ($month > 0)
                echo "</ul>";
              echo "</div>";
            }
            ?>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <?php $this->need('sidebar.php'); ?>
  </div>
</main>

<?php $this->need('footer.php'); ?>