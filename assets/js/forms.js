(function () {
  'use strict';

  var DEFAULT_ENDPOINT = 'https://112books.eu/api/submit.php';
  var FALLBACK_EMAIL = 'hola@112books.eu';

  var LANG = (document.documentElement.lang || 'ca').toLowerCase().slice(0, 2);
  if (LANG !== 'ca' && LANG !== 'es' && LANG !== 'en') { LANG = 'ca'; }

  var FULL_FORM = LANG === 'ca' ? '/legal/formulari/' : '/' + LANG + '/legal/formulari/';

  var TEXTS = {
    ca: {
      missing: "Falten camps obligatoris: %s. Torna-ho a provar o escriu-nos a " + FALLBACK_EMAIL + ".",
      invalid_email: "El correu electrònic no és vàlid. Revisa'l i torna-ho a provar.",
      invalid_url: "L'enllaç no és una adreça web vàlida (ha de començar per https://).",
      rate_limited: "Massa intents seguits des d'aquesta connexió. Espera uns minuts o escriu-nos a " + FALLBACK_EMAIL + ".",
      generic: "No s'ha pogut enviar. Torna-ho a provar o escriu-nos a " + FALLBACK_EMAIL + ".",
      full_form: "Obre el formulari complet",
      labels: {
        "nom": "el nom",
        "nom-contacte": "el nom de contacte",
        "email": "el correu electrònic",
        "telefon": "el telèfon",
        "enllac-imatges": "l'enllaç de les imatges",
        "cif": "el CIF/NIF",
        "adreca-fiscal": "l'adreça fiscal",
        "tipus": "el tipus de col·laboració",
        "visibilitat-web": "l'autorització de visibilitat al web",
        "visibilitat-publicacio": "l'autorització de visibilitat a la publicació",
        "rgpd": "l'acceptació de la política de privacitat",
        "accepta-condicions": "l'acceptació de les condicions",
        "autoritzacio-us": "l'autorització d'ús de les imatges"
      }
    },
    es: {
      missing: "Faltan campos obligatorios: %s. Vuelve a intentarlo o escríbenos a " + FALLBACK_EMAIL + ".",
      invalid_email: "El correo electrónico no es válido. Revísalo y vuelve a intentarlo.",
      invalid_url: "El enlace no es una dirección web válida (debe empezar por https://).",
      rate_limited: "Demasiados intentos seguidos desde esta conexión. Espera unos minutos o escríbenos a " + FALLBACK_EMAIL + ".",
      generic: "No se ha podido enviar. Vuelve a intentarlo o escríbenos a " + FALLBACK_EMAIL + ".",
      full_form: "Abre el formulario completo",
      labels: {
        "nom": "el nombre",
        "nom-contacte": "el nombre de contacto",
        "email": "el correo electrónico",
        "telefon": "el teléfono",
        "enllac-imatges": "el enlace de las imágenes",
        "cif": "el CIF/NIF",
        "adreca-fiscal": "la dirección fiscal",
        "tipus": "el tipo de colaboración",
        "visibilitat-web": "la autorización de visibilidad en la web",
        "visibilitat-publicacio": "la autorización de visibilidad en la publicación",
        "rgpd": "la aceptación de la política de privacidad",
        "accepta-condicions": "la aceptación de las condiciones",
        "autoritzacio-us": "la autorización de uso de las imágenes"
      }
    },
    en: {
      missing: "Some required fields are missing: %s. Please try again or write to us at " + FALLBACK_EMAIL + ".",
      invalid_email: "That email address is not valid. Please check it and try again.",
      invalid_url: "That link is not a valid web address (it must start with https://).",
      rate_limited: "Too many attempts in a row from this connection. Please wait a few minutes or write to us at " + FALLBACK_EMAIL + ".",
      generic: "It could not be sent. Please try again or write to us at " + FALLBACK_EMAIL + ".",
      full_form: "Open the full form",
      labels: {
        "nom": "your name",
        "nom-contacte": "the contact name",
        "email": "your email address",
        "telefon": "your phone number",
        "enllac-imatges": "the image link",
        "cif": "the tax ID",
        "adreca-fiscal": "the billing address",
        "tipus": "the collaboration type",
        "visibilitat-web": "the website visibility consent",
        "visibilitat-publicacio": "the publication visibility consent",
        "rgpd": "the privacy policy consent",
        "accepta-condicions": "the terms consent",
        "autoritzacio-us": "the image use consent"
      }
    }
  };

  var t = TEXTS[LANG];

  function getEndpoint(form) {
    return form.getAttribute('data-endpoint') || DEFAULT_ENDPOINT;
  }

  function getMessage(form, attr, fallback) {
    return form.getAttribute(attr) || fallback;
  }

  function labelFor(name) {
    return t.labels[name] || name;
  }

  function showMessage(form, text, isError, linkHref, linkText) {
    var box = form.querySelector('.form-msg');
    if (!box) {
      box = document.createElement('p');
      box.className = 'form-msg';
      form.appendChild(box);
    }
    box.textContent = text;
    if (linkHref) {
      box.appendChild(document.createTextNode(' '));
      var a = document.createElement('a');
      a.href = linkHref;
      a.textContent = linkText || linkHref;
      box.appendChild(a);
    }
    box.style.marginTop = '0.9rem';
    box.style.fontWeight = '600';
    box.style.color = isError ? '#b00' : '#0a7a2f';
    box.setAttribute('role', 'status');
    box.setAttribute('aria-live', 'polite');
  }

  function clearFieldMarks(form) {
    var marked = form.querySelectorAll('.field-error');
    for (var i = 0; i < marked.length; i++) {
      marked[i].classList.remove('field-error');
      marked[i].removeAttribute('aria-invalid');
    }
  }

  function markFields(form, names) {
    var first = null;
    names.forEach(function (name) {
      var el = form.querySelector('[name="' + name + '"]');
      if (el) {
        el.classList.add('field-error');
        el.setAttribute('aria-invalid', 'true');
        if (!first) { first = el; }
      }
    });
    if (first && first.focus) { first.focus(); }
  }

  document.querySelectorAll('form.contact-form').forEach(function (form) {
    var ts = form.querySelector('input[name="_ts"]');
    if (ts && !ts.value) { ts.value = String(Date.now()); }

    form.addEventListener('input', function (event) {
      var el = event.target;
      if (el && el.classList) {
        el.classList.remove('field-error');
        el.removeAttribute('aria-invalid');
      }
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (form.getAttribute('data-sending') === '1') { return; }
      form.setAttribute('data-sending', '1');

      var button = form.querySelector('button[type="submit"]');
      if (button) { button.disabled = true; }
      clearFieldMarks(form);

      var payload = {};
      new FormData(form).forEach(function (value, key) { payload[key] = value; });
      if (ts) { payload._ts = ts.value || String(Date.now()); }
      payload.lang = LANG;

      Promise.resolve().then(function () {
        return fetch(getEndpoint(form), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'omit',
          body: JSON.stringify(payload)
        });
      }).then(function (response) {
        return response.json().catch(function () {
          return { ok: false, error: 'bad_response', status: response.status };
        });
      }).then(function (data) {
        if (data && data.ok) {
          showMessage(form, getMessage(form, 'data-msg-ok', 'OK'), false);
          form.reset();
          if (ts) { ts.value = String(Date.now()); }
          return;
        }

        var msg = '';
        var linkHref = '';
        if (data && data.error === 'missing_fields' && Array.isArray(data.fields) && data.fields.length) {
          msg = t.missing.replace('%s', data.fields.map(labelFor).join(', '));
          markFields(form, data.fields);
          var absent = data.fields.filter(function (name) {
            return !form.querySelector('[name="' + name + '"]');
          });
          if (absent.length) { linkHref = FULL_FORM; }
        } else if (data && data.error === 'rate_limited') {
          msg = t.rate_limited;
        } else if (data && (data.error === 'invalid_email' || data.error === 'invalid_url')) {
          msg = t[data.error];
          markFields(form, data.error === 'invalid_email' ? ['email'] : ['enllac-imatges']);
        } else {
          msg = getMessage(form, 'data-msg-err', t.generic);
        }
        showMessage(form, msg, true, linkHref, t.full_form);
      }).catch(function () {
        showMessage(form, getMessage(form, 'data-msg-err', t.generic), true);
      }).then(function () {
        form.removeAttribute('data-sending');
        if (button) { button.disabled = false; }
      });
    });
  });
})();
