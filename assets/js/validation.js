document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("form[data-validate]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      const invalid = Array.from(
        form.querySelectorAll('input[type="number"]'),
      ).find((input) => {
        const name = input.name.toLowerCase();
        const value = Number(input.value);
        return (
          (name.includes("price") ||
            name.includes("quantity") ||
            name.includes("stock")) &&
          value < 0
        );
      });

      if (invalid) {
        event.preventDefault();
        alert("Price and quantity values cannot be negative.");
        invalid.focus();
      }
    });
  });

  const cartBody = document.querySelector("[data-cart-body]");
  const cartInput = document.querySelector("[data-cart-input]");
  const cartTotal = document.querySelector("[data-cart-total]");

  if (!cartBody || !cartInput || !cartTotal) {
    return;
  }
  const cart = [];

  function renderCart() {
    cartBody.innerHTML = "";
    let total = 0;

    cart.forEach((item, index) => {
      const subtotal = item.quantity * item.unit_price;
      total += subtotal;
      const row = document.createElement("tr");
      row.innerHTML = `
                <td>${item.name}</td>
                <td><input type="number" min="1" max="${item.stock}" value="${item.quantity}" data-index="${index}" class="cart-qty"></td>
                <td>${item.unit_price.toFixed(2)}</td>
                <td>${subtotal.toFixed(2)}</td>
                <td><button type="button" class="danger-btn" data-remove="${index}">Remove</button></td>
            `;
      cartBody.appendChild(row);
    });

    cartInput.value = JSON.stringify(
      cart.map((item) => ({
        medicine_id: item.medicine_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
      })),
    );
    cartTotal.textContent = total.toFixed(2);
  }

  document.querySelectorAll("[data-add-cart]").forEach((button) => {
    button.addEventListener("click", () => {
      const item = {
        medicine_id: Number(button.dataset.id),
        name: button.dataset.name,
        unit_price: Number(button.dataset.price),
        stock: Number(button.dataset.stock),
        quantity: 1,
      };

      const existing = cart.find((row) => row.medicine_id === item.medicine_id);
      if (existing) {
        if (existing.quantity < existing.stock) {
          existing.quantity += 1;
        }
      } else {
        cart.push(item);
      }
      renderCart();
    });
  });

  cartBody.addEventListener("input", (event) => {
    if (!event.target.classList.contains("cart-qty")) {
      return;
    }
    const index = Number(event.target.dataset.index);
    const value = Math.max(
      1,
      Math.min(Number(event.target.value), cart[index].stock),
    );
    cart[index].quantity = value;
    renderCart();
  });

  cartBody.addEventListener("click", (event) => {
    const removeIndex = event.target.dataset.remove;
    if (removeIndex !== undefined) {
      cart.splice(Number(removeIndex), 1);
      renderCart();
    }
  });

  document
    .querySelector("[data-checkout-form]")
    ?.addEventListener("submit", (event) => {
      if (cart.length === 0) {
        event.preventDefault();
        alert("Add at least one medicine to the cart.");
      }
    });
});

document.addEventListener("DOMContentLoaded", function () {
  const btn = document.getElementById("showUserForm");
  const form = document.getElementById("userFormContainer");

  btn.addEventListener("click", function () {
    if (form.style.display === "none") {
      form.style.display = "block";
      btn.textContent = "Close Form";
    } else {
      form.style.display = "none";
      btn.textContent = "Add User";
    }
  });
});

let deleteForm = null;

document.querySelectorAll(".delete-btn").forEach((button) => {
  button.addEventListener("click", () => {
    deleteForm = button.closest("form");
    document.getElementById("deleteModal").classList.add("show");
  });
});

document.getElementById("cancelDelete")?.addEventListener("click", () => {
  document.getElementById("deleteModal").classList.remove("show");
  deleteForm = null;
});

document.getElementById("confirmDelete")?.addEventListener("click", () => {
  if (deleteForm) {
    deleteForm.submit();
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const btn = document.getElementById("showSupplierForm");
  const form = document.getElementById("supplierFormContainer");

  if (!btn || !form) return;

  btn.addEventListener("click", function () {
    if (form.style.display === "none") {
      form.style.display = "block";
      btn.textContent = "Close Form";
    } else {
      form.style.display = "none";
      btn.textContent = "Add Supplier";
    }
  });
});
