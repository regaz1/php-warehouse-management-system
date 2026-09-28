'use strict';
const lines = document.getElementById('order-lines');
if (lines) {
  const formatter = new Intl.NumberFormat('el-GR', {style: 'currency', currency: 'EUR'});
  const calculate = () => {
    let total = 0;
    lines.querySelectorAll('.order-line').forEach(line => {
      const product = line.querySelector('select');
      const quantity = Number(line.querySelector('input').value || 0);
      const price = Number(product.selectedOptions[0]?.dataset.price || 0);
      const subtotal = price * quantity;
      line.querySelector('.line-total').textContent = formatter.format(subtotal);
      total += subtotal;
      line.querySelector('.remove').disabled = lines.children.length === 1;
    });
    document.getElementById('order-total').textContent = formatter.format(total);
  };
  lines.addEventListener('input', calculate);
  lines.addEventListener('change', calculate);
  lines.addEventListener('click', event => {
    if (event.target.matches('.remove') && lines.children.length > 1) {
      event.target.closest('.order-line').remove(); calculate();
    }
  });
  document.getElementById('add-line').addEventListener('click', () => {
    if (lines.children.length >= 20) return;
    const clone = lines.firstElementChild.cloneNode(true);
    clone.querySelector('select').value = '';
    clone.querySelector('input').value = 1;
    lines.append(clone); calculate();
  });
  calculate();
}
