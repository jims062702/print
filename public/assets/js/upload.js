import { PRICES, computePrice } from '/public/assets/js/pricing.js';

const RECENT_KEY = 'ps_recent_uploads';

function saveRecent(orders){
  try{ localStorage.setItem(RECENT_KEY, JSON.stringify(orders.slice(0, 10))); }catch(e){}
}
function loadRecent(){
  try{ const v = localStorage.getItem(RECENT_KEY); return v ? JSON.parse(v) : []; }catch(e){ return []; }
}

function createOptionSelect(options, selected){
  const sel = document.createElement('select');
  for (const [value, label] of options) {
    const opt = document.createElement('option');
    opt.value = value; opt.textContent = label;
    if (value === selected) opt.selected = true;
    sel.appendChild(opt);
  }
  return sel;
}

function getColorOptions(category){
  if (category === 'imageOnly') {
    return [ ['bw','Black & White'], ['half','Half Page Colored'], ['full','Whole Page Colored'] ];
  }
  return [ ['bw','Black & White'], ['partial','Partially Colored'], ['full','Whole Page Colored'] ];
}

function renderItemRow(item, onChange){
  const row = document.createElement('div');
  row.className = 'u-item';

  const left = document.createElement('div');
  left.innerHTML = `<div class="u-row"><strong>${item.name}</strong><span style="color:var(--muted)">${item.size} • ${item.type}</span></div>`;

  const right = document.createElement('div');

  const catSel = createOptionSelect([
    ['textOnly', 'Text Only'],
    ['withImage', 'With Image'],
    ['imageOnly', 'Image Only'],
    ['photocopy', 'Photocopy'],
  ], item.category);

  const colorSel = createOptionSelect(getColorOptions(item.category), item.colorMode);

  const paperSel = createOptionSelect([
    ['A4','A4'], ['Short','Short'], ['Long','Long']
  ], item.paperSize);

  const qtyInput = document.createElement('input'); qtyInput.type='number'; qtyInput.min='1'; qtyInput.value=String(item.quantity||1);
  const pagesInput = document.createElement('input'); pagesInput.type='number'; pagesInput.min='1'; pagesInput.value=String(item.pages||1);
  const b2b = document.createElement('label'); b2b.innerHTML = `<input type="checkbox" ${item.isBackToBack?'checked':''}/> Back-to-Back`;
  const priceEl = document.createElement('div'); priceEl.className='price';

  const apply = () => {
    item.category = catSel.value;
    // Rebuild color options if category changed
    const allowed = getColorOptions(item.category).map(o=>o[0]);
    if (!allowed.includes(colorSel.value)) {
      const old = colorSel.value;
      colorSel.innerHTML = '';
      getColorOptions(item.category).forEach(([v,l])=>{
        const opt = document.createElement('option'); opt.value=v; opt.textContent=l; colorSel.appendChild(opt);
      });
      colorSel.value = allowed[0];
    }
    item.colorMode = colorSel.value;
    item.paperSize = paperSel.value;
    item.quantity = Math.max(1, parseInt(qtyInput.value||'1'));
    item.pages = Math.max(1, parseInt(pagesInput.value||'1'));
    item.isBackToBack = b2b.querySelector('input').checked;
    item.price = computePrice(item);
    priceEl.textContent = `₱${item.price.toFixed(2)}`;
    onChange();
  };

  [catSel, colorSel, paperSel, qtyInput, pagesInput, b2b.querySelector('input')].forEach(el=>{
    el.addEventListener('change', apply);
    el.addEventListener('input', apply);
  });

  apply();

  right.appendChild(Object.assign(document.createElement('div'), { className:'u-row', append: (...els)=>els.forEach(e=>right.firstChild.appendChild(e)) }));
  right.firstChild.append('Category: ', catSel, ' Color: ', colorSel, ' Paper: ', paperSel);

  const r2 = document.createElement('div'); r2.className='u-row';
  r2.append('Qty: ', qtyInput, ' Pages: ', pagesInput, b2b, priceEl);
  right.appendChild(r2);

  row.appendChild(left); row.appendChild(right);
  return row;
}

function formatOrderPayload(order){
  const fd = new FormData();
  order.items.forEach((item, idx)=>{
    if (item.file) fd.append('files[]', item.file, item.file.name);
    fd.append(`items[${idx}][name]`, item.name);
    fd.append(`items[${idx}][type]`, item.type);
    fd.append(`items[${idx}][size]`, item.size);
    fd.append(`items[${idx}][category]`, item.category);
    fd.append(`items[${idx}][colorMode]`, item.colorMode);
    fd.append(`items[${idx}][paperSize]`, item.paperSize);
    fd.append(`items[${idx}][quantity]`, String(item.quantity||1));
    fd.append(`items[${idx}][pages]`, String(item.pages||1));
    fd.append(`items[${idx}][isBackToBack]`, item.isBackToBack? '1':'0');
    fd.append(`items[${idx}][price]`, String(item.price||0));
  });
  fd.append('note', order.note||'');
  return fd;
}

export function initUpload(){
  const drop = document.getElementById('drop');
  const input = document.getElementById('file-input');
  const list = document.getElementById('list');
  const totalEl = document.getElementById('total');
  const submitBtn = document.getElementById('submit');
  const noteEl = document.getElementById('note');
  const reminder = document.getElementById('reminder');

  reminder.textContent = 'You can upload any image to submit your request so the admin will be notified.';

  const order = { items: [], note: '' };

  function updateTotal(){
    const total = order.items.reduce((sum, it) => sum + (it.price||0), 0);
    totalEl.textContent = `₱${total.toFixed(2)}`;
  }

  function renderList(){
    list.innerHTML = '';
    order.items.forEach(item => {
      const row = renderItemRow(item, updateTotal);
      list.appendChild(row);
    });
    updateTotal();
  }

  function addFiles(files){
    Array.from(files).forEach(file=>{
      const item = {
        file,
        name: file.name,
        type: file.type || 'application/octet-stream',
        size: `${(file.size/1024).toFixed(1)} KB`,
        category: 'textOnly',
        colorMode: 'bw',
        paperSize: 'A4',
        isBackToBack: false,
        pages: 1,
        quantity: 1,
      };
      item.price = computePrice(item);
      order.items.push(item);
    });
    renderList();
  }


  drop.addEventListener('dragover', e=>{ e.preventDefault(); drop.style.background='rgba(255,255,255,0.05)'; });
  drop.addEventListener('dragleave', e=>{ drop.style.background='transparent'; });
  drop.addEventListener('drop', e=>{ e.preventDefault(); drop.style.background='transparent'; addFiles(e.dataTransfer.files); });
  drop.addEventListener('click', ()=> input.click());
  input.addEventListener('change', ()=> addFiles(input.files));

  submitBtn.addEventListener('click', async ()=>{
    order.note = noteEl.value.trim();
    if (order.items.length === 0) { alert('Please add at least one file.'); return; }
    const fd = formatOrderPayload(order);
    submitBtn.disabled = true; submitBtn.textContent = 'Submitting...';
    try {
      const res = await fetch('/api/upload.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message||'Upload failed');
      // Save recent
      const recent = loadRecent();
      recent.unshift({ id: data.id, createdAt: new Date().toISOString(), items: order.items.map(({name, price})=>({name, price})) });
      saveRecent(recent);
      const status = document.getElementById('status');
      if (status) { status.style.color = '#2ecc71'; status.textContent = 'Submitted successfully! Admin has been notified.'; }
      // Reset current order state
      order.items = [];
      renderList();
    } catch (e) {
      const status = document.getElementById('status');
      if (status) { status.style.color = '#ff5c7a'; status.textContent = 'Error: ' + e.message; }
    } finally {
      submitBtn.disabled = false; submitBtn.textContent = 'Submit Request';
    }
  });
}

