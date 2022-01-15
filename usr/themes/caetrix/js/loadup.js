/* Copyright (c) 2017 by metheno. All Rights Reserved.
Website: https://www.project-sophia.jp/ */

// Open in new tab.
function openExternalLinks() {
  for (var a = document.getElementsByTagName("a"), i = 0; i < a.length; i++) {
    var c = a[i];
    c.getAttribute("href") && c.hostname !== location.hostname && (c.target = "_blank");
  }
}
openExternalLinks();

hljs.initHighlightingOnLoad(); // highlight.js
