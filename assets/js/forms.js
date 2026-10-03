(function () {
  'use strict';
  var DEFAULT_ENDPOINT = 'https://112books.eu/api/submit.php';

  function getEndpoint(form) {
    return form.getAttribute('data-endpoint') || DEFAULT_ENDPOINT;
  }

  function getMessage(form, attr, fallback) {
    return form.getAttribute(attr) || fallback;
  }

  function showMessage(form, text, isError) {
    var box = form.querySelector('.form-msg');
    if (!box) {
      box = document.createElement('p');
      box.className = 'form-msg';
      form.appendChild(box);
    }
    box.textContent = text;
    box.style.marginTop = '0.9rem';
    box.style.fontWeight = '600';
    box.style.color = isError ? '#b00' : '#0a7a2f';
    box.setAttribute('role', 'status');
  }

  document.querySelectorAll('form.contact-form').forEach(function (form) {
    var ts = form.querySelector('input[name="_ts"]');
    if (ts && !ts.value) { ts.value = String(Date.now()); }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var button = form.querySelector('button[type="submit"]');
      if (button) { button.disabled = true; }

      var payload = {};
      new FormData(form).forEach(function (value, key) { payload[key] = value; });
      if (!payload._ts) { payload._ts = String(Date.now()); }

      fetch(getEndpoint(form), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function (response) {
        return response.json().catch(function () { return { ok: false }; });
      }).then(function (data) {
        if (data && data.ok) {
          showMessage(form, getMessage(form, 'data-msg-ok', 'OK'), false);
          form.reset();
          if (ts) { ts.value = String(Date.now()); }
        } else {
          showMessage(form, getMessage(form, 'data-msg-err', 'Error'), true);
        }
      }).catch(function () {
        showMessage(form, getMessage(form, 'data-msg-err', 'Error'), true);
      }).then(function () {
        if (button) { button.disabled = false; }
      });
    });
  });
})();
