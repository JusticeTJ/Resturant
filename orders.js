const orderCartKey = 'hungrymans_cart';
const orderModal = document.getElementById('orderModal');
const orderToast = document.getElementById('orderToast');
const cartLines = document.getElementById('cartLines');
const cartCount = document.getElementById('cartCount');
let orderCart = [];
let activeOrderId = 0;

try {
    orderCart = JSON.parse(localStorage.getItem(orderCartKey) || '[]');
    if (!Array.isArray(orderCart)) orderCart = [];
} catch (error) {
    orderCart = [];
}

function saveOrderCart() {
    localStorage.setItem(orderCartKey, JSON.stringify(orderCart));
    renderOrderCart();
}

function renderOrderCart() {
    if (!cartLines) return;
    cartLines.replaceChildren();
    cartCount.textContent = String(orderCart.reduce((sum, item) => sum + item.quantity, 0));
    let total = 0;

    for (const item of orderCart) {
        total += item.price * item.quantity;
        const row = document.createElement('div');
        row.className = 'cart-line';
        const label = document.createElement('strong');
        label.textContent = `${item.name} · ₦${(item.price * item.quantity).toLocaleString('en-NG')}`;
        const controls = document.createElement('div');
        controls.className = 'cart-line-controls';
        const decrement = document.createElement('button');
        decrement.type = 'button';
        decrement.className = 'cart-quantity-button';
        decrement.textContent = '−';
        decrement.setAttribute('aria-label', `Remove one ${item.name}`);
        decrement.addEventListener('click', () => changeOrderQuantity(item.id, -1));
        const quantity = document.createElement('span');
        quantity.textContent = String(item.quantity);
        const increment = document.createElement('button');
        increment.type = 'button';
        increment.className = 'cart-quantity-button';
        increment.textContent = '+';
        increment.setAttribute('aria-label', `Add one ${item.name}`);
        increment.addEventListener('click', () => changeOrderQuantity(item.id, 1));
        controls.append(decrement, quantity, increment);
        row.append(label, controls);
        cartLines.append(row);
    }

    document.getElementById('cartTotal').textContent = `₦${total.toLocaleString('en-NG')}`;
    document.getElementById('confirmOrderButton').disabled = orderCart.length === 0;
}

function changeOrderQuantity(itemId, change) {
    const item = orderCart.find(cartItem => cartItem.id === itemId);
    if (!item) return;
    item.quantity += change;
    if (item.quantity < 1) orderCart = orderCart.filter(cartItem => cartItem.id !== itemId);
    if (item.quantity > 20) item.quantity = 20;
    saveOrderCart();
}

function openOrderCart() {
    orderToast.hidden = true;
    document.getElementById('cartView').hidden = false;
    document.getElementById('transferView').hidden = true;
    document.getElementById('orderModalTitle').textContent = 'Your cart';
    document.getElementById('orderModalSubtitle').textContent = 'Review your items before confirming.';
    renderOrderCart();
    orderModal.classList.add('show');
    orderModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeOrderCart() {
    orderModal.classList.remove('show');
    orderModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = 'auto';
}

function showExistingOrderTransfer(orderId, orderNumber, total) {
    activeOrderId = Number(orderId);
    orderToast.hidden = true;
    document.getElementById('cartView').hidden = true;
    document.getElementById('transferView').hidden = false;
    document.getElementById('orderModalTitle').textContent = 'Complete your transfer';
    document.getElementById('orderModalSubtitle').textContent = 'Your order is waiting for payment.';
    document.getElementById('createdOrderLabel').textContent = `Order ${orderNumber}`;
    document.getElementById('transferAmount').textContent = `₦${Number(total).toLocaleString('en-NG')}`;
    document.getElementById('transferResponse').textContent = '';
    document.getElementById('transferSentButton').disabled = false;
    orderModal.classList.add('show');
    orderModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function addOrderItem(card) {
    const itemId = Number(card.dataset.menuId);
    const current = orderCart.find(item => item.id === itemId);
    if (current) {
        current.quantity = Math.min(20, current.quantity + 1);
    } else {
        orderCart.push({
            id: itemId,
            name: card.dataset.menuName,
            price: Number(card.dataset.menuPrice),
            quantity: 1
        });
    }
    saveOrderCart();
    openOrderCart();
}

document.querySelectorAll('.menu-card[data-menu-id] .order-now').forEach(button => {
    button.addEventListener('click', () => addOrderItem(button.closest('.menu-card')));
});
const requestedItemId = Number(new URLSearchParams(window.location.search).get('add_item') || 0);
if (requestedItemId > 0) {
    const requestedCard = document.querySelector(`.menu-card[data-menu-id="${requestedItemId}"]`);
    if (requestedCard) {
        addOrderItem(requestedCard);
        window.history.replaceState(null, '', `${window.location.pathname}#menu`);
    }
}
document.getElementById('cartOpenButton')?.addEventListener('click', openOrderCart);
document.getElementById('closeOrderModal')?.addEventListener('click', closeOrderCart);
document.querySelectorAll('.customer-order-pay').forEach(button => {
    button.addEventListener('click', () => showExistingOrderTransfer(button.dataset.orderId, button.dataset.orderNumber, button.dataset.orderTotal));
});
orderModal?.addEventListener('click', event => {
    if (event.target === orderModal) closeOrderCart();
});

document.getElementById('confirmOrderButton')?.addEventListener('click', async event => {
    const button = event.currentTarget;
    if (!orderCart.length) return;
    if (!isCustomerSignedIn) {
        closeOrderCart();
        openLogIn();
        const message = document.getElementById('login-message');
        message.textContent = 'Sign in before placing an order. Your cart is saved in this browser.';
        message.classList.add('visible');
        return;
    }

    button.disabled = true;
    button.textContent = 'Creating order...';
    const data = new FormData();
    data.set('csrf_token', orderCsrfToken);
    data.set('action', 'create_order');
    data.set('items', JSON.stringify(orderCart.map(item => ({ id: item.id, quantity: item.quantity }))));
    try {
        const response = await fetch('place-order.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Could not create your order.');
        activeOrderId = Number(result.order_id);
        orderCart = [];
        saveOrderCart();
        document.getElementById('cartView').hidden = true;
        document.getElementById('transferView').hidden = false;
        document.getElementById('orderModalTitle').textContent = 'Complete your transfer';
        document.getElementById('orderModalSubtitle').textContent = 'Your order is saved and awaiting payment.';
        document.getElementById('createdOrderLabel').textContent = `Order ${result.order_number}`;
        document.getElementById('transferAmount').textContent = `₦${Number(result.total).toLocaleString('en-NG')}`;
        document.getElementById('transferResponse').textContent = '';
        document.getElementById('transferSentButton').disabled = false;
    } catch (error) {
        orderToast.textContent = error.message;
        orderToast.hidden = false;
    } finally {
        button.textContent = 'Confirm order';
        button.disabled = orderCart.length === 0;
    }
});

document.getElementById('transferSentButton')?.addEventListener('click', async event => {
    const button = event.currentTarget;
    const data = new FormData();
    data.set('csrf_token', orderCsrfToken);
    data.set('action', 'transfer_sent');
    data.set('order_id', String(activeOrderId));
    button.disabled = true;
    try {
        const response = await fetch('place-order.php', { method: 'POST', body: data });
        const result = await response.json();
        document.getElementById('transferResponse').textContent = result.message || 'Transfer status updated.';
        if (result.success) {
            setTimeout(() => window.location.reload(), 1400);
        } else {
            button.disabled = false;
        }
    } catch (error) {
        document.getElementById('transferResponse').textContent = 'Could not reach the server. Please try again.';
        button.disabled = false;
    }
});

document.getElementById('transferNotSentButton')?.addEventListener('click', () => {
    closeOrderCart();
    window.location.reload();
});

const customerStatusClasses = ['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'cancelled'];
function paymentStatusLabel(status) {
    return ({
        confirmed: 'Transfer confirmed',
        submitted: 'Awaiting transfer verification',
        rejected: 'Transfer not confirmed',
        not_sent: 'Not sent yet'
    })[status] || 'Not sent yet';
}

async function refreshCustomerOrders() {
    if (!isCustomerSignedIn) return;
    try {
        const response = await fetch('customer-orders.php', { cache: 'no-store' });
        if (!response.ok) return;
        const result = await response.json();
        for (const order of result.orders || []) {
            const card = document.querySelector(`[data-customer-order-id="${Number(order.id)}"]`);
            if (!card) continue;
            const status = card.querySelector('.customer-order-status');
            status.textContent = order.status;
            status.classList.remove(...customerStatusClasses.map(value => `status-${value}`));
            status.classList.add(`status-${String(order.status).toLowerCase()}`);
            card.querySelector('.customer-payment-status').textContent = paymentStatusLabel(order.payment_status);
            const payButton = card.querySelector('.customer-order-pay');
            if (payButton) payButton.hidden = !['not_sent', 'rejected'].includes(order.payment_status);
        }
    } catch (error) {
    }
}

refreshCustomerOrders();
if (isCustomerSignedIn) {
    setInterval(refreshCustomerOrders, 10000);
    window.addEventListener('focus', refreshCustomerOrders);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshCustomerOrders();
    });
}
renderOrderCart();