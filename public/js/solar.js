const billRange = document.getElementById('billRange');
if (billRange) {
  const bill = document.getElementById('billOutput');
  const saved = document.getElementById('savingOutput');
  const annual = document.getElementById('annualOutput');
  const format = (n) => Math.round(n).toLocaleString('en-PH');
  const refresh = () => {
    const value = Number(billRange.value);
    const monthlySaving = value * 0.6;
    bill.value = format(value);
    saved.value = format(monthlySaving);
    annual.value = format(monthlySaving * 12);
  };
  billRange.addEventListener('input', refresh);
  refresh();
}
