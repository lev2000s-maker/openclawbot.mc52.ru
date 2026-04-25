(function () {
  'use strict';

  var pairBtn = document.getElementById('addPair');
  if (pairBtn) {
    var tpl = document.getElementById('newpairTpl');
    var container = document.getElementById('new-settings');
    if (tpl && container) {
      pairBtn.addEventListener('click', function () {
        var node = document.importNode(tpl.content, true);
        container.appendChild(node);
      });
    }
  }
})();
