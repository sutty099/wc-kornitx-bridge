(function(){
  if (!window.KX_SMARTLINK) return;
  const cfg = window.KX_SMARTLINK;
  const iframe = document.getElementById('kx-smartlink-iframe');
  if (!iframe) return;

  const iframeOrigin = 'https://g3d-app.com';
  const baseUrl = cfg.smartlink_url;
  const meo = window.location.origin;
  const mei = Math.random().toString(16).substr(2);

  function appendParamsToSmartlink(url, params){
    const parts = url.split('#');
    const prefix = parts[0];
    const hash = parts[1] || '';
    const sep = hash.length ? '&' : '';
    const extra = Object.keys(params).map(k => `${encodeURIComponent(k)}=${encodeURIComponent(params[k])}`).join('&');
    return prefix + '#' + hash + sep + extra;
  }

  const hashParams = { a2c: 'postMessage', meo, mei };
  if (cfg.pj) hashParams.pj = cfg.pj;
  const src = appendParamsToSmartlink(baseUrl, hashParams);
  iframe.src = src;

  function logSmartlinkPayload(payload) {
    if (!cfg.debug) return;
    try {
      const fd = new FormData();
      fd.append('action', 'kx_log_smartlink_message');
      fd.append('payload', JSON.stringify(payload));
      fetch(cfg.ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(()=>{});
    } catch (e) {}
  }

  function addToCartFromSmartlink(item){
    const payload = new FormData();
    payload.append('action', 'kx_iframe_add_to_cart');
    payload.append('nonce', cfg.nonce);
    payload.append('product_id', String(cfg.product_id));
    payload.append('print_job_ref', item.ref);
    if (item.sku) payload.append('sku', item.sku);
    if (item.quantity) payload.append('quantity', String(item.quantity));

    try {
      const thumbs = item.thumbnails || (item.extra && item.extra.state && item.extra.state.thumbnails) || [];
      payload.append('thumbnails', JSON.stringify(thumbs));
    } catch(e){ payload.append('thumbnails', '[]'); }

    try { if (item.variant) payload.append('variant', JSON.stringify(item.variant)); } catch(e){}

    return fetch(cfg.ajax_url, { method: 'POST', body: payload, credentials: 'same-origin' })
      .then(r => r.json())
      .then(res => { if (!res || !res.success) throw new Error((res && res.data && res.data.message) || 'Unknown error'); return res.data; });
  }

  window.addEventListener('message', function(e){
    logSmartlinkPayload({ origin: e.origin, data: e.data });
    if (e.origin !== iframeOrigin) return;
    const data = e.data || {};
    if (data.id !== mei) return;

    switch(data.name){
      case 'ADD_TO_CART_CALLBACK':
        try {
          const items = (data.body && data.body.items) ? data.body.items : [];
          if (!items.length) return;
          Promise.all(items.map(addToCartFromSmartlink))
            .then(() => {
              var cartUrl = (window.wc_add_to_cart_params && wc_add_to_cart_params.cart_url) || '/cart/';
              window.location.href = cartUrl;
            })
            .catch(err => { console.error('Failed to add to cart', err); alert('Sorry, we could not add this item to your cart. Please try again.'); });
        } catch(err){ console.error('ADD_TO_CART_CALLBACK handling error', err); }
        break;
      case 'IFRAME_RESIZE':
        if (data.body && data.body.height){ iframe.style.height = String(data.body.height) + 'px'; }
        break;
      default: break;
    }
  });
})();
