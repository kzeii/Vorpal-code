document.querySelectorAll('[data-package]').forEach((link) => {
  link.addEventListener('click', () => {
    const select = document.querySelector('select[name="package_id"]');
    if (select) select.value = link.dataset.package;
  });
});

const customerSearch = document.getElementById('customerSearch');
if (customerSearch) {
  customerSearch.addEventListener('input', () => {
    const query = customerSearch.value.trim().toLowerCase();
    document.querySelectorAll('#customerTable tbody tr').forEach((row) => {
      row.hidden = !row.dataset.search.includes(query);
    });
  });
}

const filterConfigs = [
  { id: 'quotes', selector: '.leadCard', label: 'Search quotes' },
  { id: 'installations', selector: '.installAdminCard', label: 'Search projects' },
  { id: 'service-requests', selector: '.appTable tbody tr', label: 'Search requests' },
  { id: 'activity-log', selector: '.activityItem', label: 'Search activity' },
];

filterConfigs.forEach(({ id, selector, label }) => {
  const section = document.getElementById(id);
  if (!section) return;
  const items = [...section.querySelectorAll(selector)];
  if (items.length < 2) return;
  const heading = section.querySelector('.appSectionHeading');
  if (!heading) return;

  const tools = document.createElement('div');
  tools.className = 'listTools';
  const search = document.createElement('input');
  search.type = 'search';
  search.placeholder = label;
  search.setAttribute('aria-label', label);
  tools.append(search);

  if (id !== 'activity-log') {
    const statuses = [...new Set(items.map((item) => item.querySelector('.pillStatus')?.textContent.trim()).filter(Boolean))];
    if (statuses.length) {
      const select = document.createElement('select');
      select.setAttribute('aria-label', 'Filter by status');
      select.innerHTML = '<option value="">All statuses</option>';
      statuses.sort().forEach((status) => {
        const option = document.createElement('option');
        option.value = status.toLowerCase();
        option.textContent = status;
        select.append(option);
      });
      tools.append(select);
      select.addEventListener('change', applyFilters);
      tools.statusSelect = select;
    }
  }

  const counter = heading.querySelector('.sectionCounter');
  if (counter) counter.replaceWith(tools);
  else heading.append(tools);

  function applyFilters() {
    const query = search.value.trim().toLowerCase();
    const status = tools.statusSelect?.value || '';
    items.forEach((item) => {
      const itemStatus = item.querySelector('.pillStatus')?.textContent.trim().toLowerCase() || '';
      item.hidden = !item.textContent.toLowerCase().includes(query) || (status && itemStatus !== status);
    });
  }
  search.addEventListener('input', applyFilters);
});

document.querySelectorAll('.sideLink').forEach((link) => {
  link.addEventListener('click', () => {
    document.querySelectorAll('.sideLink').forEach((item) => item.classList.remove('active'));
    link.classList.add('active');
  });
});

const staffBillForm = document.getElementById('staffBillForm');
if (staffBillForm) {
  const fields = Object.fromEntries(['usage', 'rate', 'charges', 'credits'].map((name) => [name, staffBillForm.elements.namedItem(name)]));
  const outputs = {
    total: document.getElementById('billTotal'),
    energy: document.getElementById('energyCharge'),
    charges: document.getElementById('otherCharges'),
    credits: document.getElementById('billCredits'),
  };
  const formatPeso = (value) => Math.max(0, value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const recalculateBill = () => {
    const energy = Math.max(0, Number(fields.usage.value) || 0) * Math.max(0, Number(fields.rate.value) || 0);
    const charges = Math.max(0, Number(fields.charges.value) || 0);
    const credits = Math.max(0, Number(fields.credits.value) || 0);
    const total = Math.max(0, energy + charges - credits);
    outputs.energy.value = formatPeso(energy);
    outputs.charges.value = formatPeso(charges);
    outputs.credits.value = formatPeso(credits);
    outputs.total.value = formatPeso(total);
    return { energy, charges, credits, total };
  };
  staffBillForm.addEventListener('input', recalculateBill);
  staffBillForm.addEventListener('reset', () => window.setTimeout(recalculateBill, 0));
  recalculateBill();

  const copyButton = document.getElementById('copyBillEstimate');
  const copyMessage = document.getElementById('billCopyMessage');
  copyButton.addEventListener('click', async () => {
    const values = recalculateBill();
    const summary = `Estimated electricity bill: PHP ${formatPeso(values.total)} (energy: PHP ${formatPeso(values.energy)}, other charges: PHP ${formatPeso(values.charges)}, credits: PHP ${formatPeso(values.credits)}).`;
    try {
      await navigator.clipboard.writeText(summary);
      copyMessage.textContent = 'Estimate copied.';
    } catch {
      copyMessage.textContent = summary;
    }
  });
}
