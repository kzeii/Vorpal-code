(() => {
  const tokenPattern = /^[a-f0-9]{32}$/;
  const script = document.currentScript;
  const url = new URL(window.location.href);
  const urlToken = url.searchParams.get('tab_context');
  const storedToken = sessionStorage.getItem('sunSonSolarTabContext');
  const pageToken = script?.dataset.tabContext;
  const randomToken = () => {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID().replaceAll('-', '');
    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);
    return Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
  };
  const token = [urlToken, storedToken, pageToken].find(value => tokenPattern.test(value || '')) || randomToken();
  sessionStorage.setItem('sunSonSolarTabContext', token);

  const addTokenToLink = link => {
    if (!link.href || link.getAttribute('href')?.startsWith('#')) return;
    const target = new URL(link.href, window.location.href);
    if (target.origin !== window.location.origin) return;
    if (target.searchParams.get('mode') === 'login' && !target.searchParams.has('tab_context')) {
      target.searchParams.set('tab_context', randomToken());
    } else if (!target.searchParams.has('tab_context')) {
      target.searchParams.set('tab_context', token);
    }
    link.href = target.href;
  };

  document.querySelectorAll('a[href]').forEach(addTokenToLink);

  document.querySelectorAll('form').forEach(form => {
    if (!form.querySelector('input[name="tab_context"]')) {
      const field = document.createElement('input');
      field.type = 'hidden';
      field.name = 'tab_context';
      field.value = token;
      form.appendChild(field);
    }
  });
})();
