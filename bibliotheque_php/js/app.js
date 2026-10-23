// Confirmation avant suppression / retour
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!confirm(form.dataset.confirm)) e.preventDefault();
  });
});

// Recherche instantanée dans le tableau
var search = document.getElementById('search');
if (search) {
  var table = document.getElementById(search.dataset.table);
  search.addEventListener('input', function () {
    var q = search.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function (row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// Masquer automatiquement les messages
var flash = document.querySelector('.flash');
if (flash) {
  setTimeout(function () {
    flash.style.opacity = '0';
    setTimeout(function () { flash.remove(); }, 500);
  }, 4000);
}
