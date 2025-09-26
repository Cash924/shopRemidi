// dist/js/cart.js
const Cart = (() => {
  const KEY = 'rc.cart.v1';

  const load = () => { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch { return []; } };
  const save = (arr) => localStorage.setItem(KEY, JSON.stringify(arr));
  const count = (arr = load()) => arr.reduce((n, i) => n + Number(i.qty || 0), 0);

  const syncBadge = (sel = '#cartCount') => {
    const el = document.querySelector(sel);
    if (el) el.textContent = String(count());
  };

  const add = (item, qty = 1) => {
    const cart = load();
    const i = cart.findIndex(x => x.sku === item.sku);
    if (i > -1) cart[i].qty += qty;
    else cart.push({ sku:item.sku, name:item.name, price:Number(item.price||0), image:item.image||'', qty:Number(qty)||1 });
    save(cart); syncBadge(); return cart;
  };

  const updateQty = (sku, qty) => {
    const cart = load();
    const i = cart.findIndex(x => x.sku === sku);
    if (i > -1) {
      cart[i].qty = Math.max(0, Number(qty) || 0);
      if (cart[i].qty === 0) cart.splice(i, 1);
      save(cart); syncBadge();
    }
    return cart;
  };

  const remove = (sku) => updateQty(sku, 0);
  const clear = () => { save([]); syncBadge(); };

  return { KEY, load, save, count, syncBadge, add, updateQty, remove, clear };
})();

// keep badge correct on any page load
document.addEventListener('DOMContentLoaded', () => Cart.syncBadge());
