let cart = [];
let globalDiscount = 0;

document.addEventListener('DOMContentLoaded', () => {
    renderProducts(productsData);

    const searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = productsData.filter(p => 
                p.name.toLowerCase().includes(term) || 
                (p.sku && p.sku.toLowerCase().includes(term)) || 
                (p.barcode && p.barcode.toLowerCase().includes(term))
            );
            renderProducts(filtered);
        });
    }
});

function renderProducts(productsList) {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = '';
    
    productsList.forEach(p => {
        const inStock = parseFloat(p.stock_quantity) > 0;
        const div = document.createElement('div');
        div.className = `pos-item ${!inStock ? 'disabled' : ''}`;
        if(inStock) {
            div.onclick = () => addToCart(p);
        }
        
        div.innerHTML = `
            <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem; line-height: 1.2;">${p.name}</div>
            <div style="color: var(--primary); font-weight: bold; margin-bottom: 0.25rem;">${currencySymbol} ${parseFloat(p.selling_price).toFixed(2)}</div>
            <div style="font-size: 0.75rem; color: ${inStock ? 'var(--secondary)' : 'var(--danger)'};">Stock: ${p.stock_quantity}</div>
        `;
        grid.appendChild(div);
    });
}

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        if (existing.qty + 1 > parseFloat(product.stock_quantity)) {
            alert('Cannot exceed available stock!');
            return;
        }
        existing.qty += 1;
    } else {
        if (parseFloat(product.stock_quantity) < 1) {
            alert('Out of stock!');
            return;
        }
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.selling_price),
            taxPercent: parseFloat(product.tax_percent),
            qty: 1,
            maxQty: parseFloat(product.stock_quantity)
        });
    }
    renderCart();
}

function changeQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if(item) {
        let newQty = item.qty + delta;
        if(newQty > item.maxQty) {
            alert('Cannot exceed available stock!');
            return;
        }
        if(newQty <= 0) {
            removeFromCart(id);
        } else {
            item.qty = newQty;
            renderCart();
        }
    }
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

function clearCart() {
    if(confirm('Clear the current cart?')) {
        cart = [];
        globalDiscount = 0;
        document.getElementById('input_global_discount').value = 0;
        document.getElementById('input_amount_paid').value = '';
        renderCart();
    }
}

function setDiscount() {
    let amt = prompt('Enter discount amount:', globalDiscount);
    if(amt !== null) {
        amt = parseFloat(amt);
        if(!isNaN(amt) && amt >= 0) {
            globalDiscount = amt;
            document.getElementById('input_global_discount').value = amt;
            renderCart();
        }
    }
}

function renderCart() {
    const tbody = document.getElementById('cartBody');
    tbody.innerHTML = '';
    
    let subtotal = 0;
    let totalTax = 0;
    
    cart.forEach(item => {
        const itemSub = item.qty * item.price;
        const itemTax = itemSub * (item.taxPercent / 100);
        const itemTotal = itemSub + itemTax;
        
        subtotal += itemSub;
        totalTax += itemTax;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="hidden" name="product_id[]" value="${item.id}">
                <input type="hidden" name="price[]" value="${item.price}">
                <input type="hidden" name="quantity[]" value="${item.qty}">
                <div style="font-size: 0.875rem; font-weight: 500;">${item.name}</div>
                <div style="font-size: 0.75rem; color: var(--secondary);">@ ${item.price.toFixed(2)}</div>
            </td>
            <td style="text-align: center;">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="qty-btn" onclick="changeQty(${item.id}, -1)">-</button>
                    <span style="font-weight: 500; min-width: 20px; text-align: center;">${item.qty}</span>
                    <button type="button" class="qty-btn" onclick="changeQty(${item.id}, 1)">+</button>
                </div>
            </td>
            <td style="text-align: right; font-weight: 500;">${itemTotal.toFixed(2)}</td>
            <td style="text-align: right;">
                <button type="button" style="background: none; border: none; color: var(--danger); cursor: pointer;" onclick="removeFromCart(${item.id})"><i class="fas fa-times"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    const grandTotal = subtotal + totalTax - globalDiscount;
    
    document.getElementById('txtSubtotal').innerText = subtotal.toFixed(2);
    document.getElementById('txtTax').innerText = totalTax.toFixed(2);
    document.getElementById('txtDiscount').innerText = globalDiscount.toFixed(2);
    document.getElementById('txtGrandTotal').innerText = Math.max(0, grandTotal).toFixed(2);
    
    calcChange();
}

function calcChange() {
    const grandTotal = parseFloat(document.getElementById('txtGrandTotal').innerText) || 0;
    const amountPaid = parseFloat(document.getElementById('input_amount_paid').value) || 0;
    
    let change = amountPaid - grandTotal;
    const txtChange = document.getElementById('txtChange');
    
    if(change >= 0) {
        txtChange.innerText = change.toFixed(2);
        txtChange.style.color = 'var(--primary)';
    } else {
        txtChange.innerText = change.toFixed(2) + ' (Pending)';
        txtChange.style.color = 'var(--danger)';
    }
}

function submitSale() {
    if(cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    
    const grandTotal = parseFloat(document.getElementById('txtGrandTotal').innerText) || 0;
    const amountPaidInput = document.getElementById('input_amount_paid');
    const paidAmount = parseFloat(amountPaidInput.value) || 0;
    
    if(amountPaidInput.value === '') {
        amountPaidInput.value = grandTotal.toFixed(2);
    }
    
    if(paidAmount < grandTotal) {
        if(!confirm('Amount paid is less than the total. Are you sure you want to proceed?')) {
            return;
        }
    }
    
    processCheckout(paymentMethod, paidAmount);
}
