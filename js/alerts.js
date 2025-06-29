const alertPlaceholder = document.getElementById('liveAlertPlaceholder');

    const appendAlert = (message, type) => {
      // Удалим лишние алерты, если их больше двух
      while (alertPlaceholder.children.length >= 2) {
        alertPlaceholder.removeChild(alertPlaceholder.firstChild);
      }

      const wrapper = document.createElement('div');
      wrapper.className = `alert alert-${type} alert-dismissible fade show`;
      wrapper.role = 'alert';
      wrapper.innerHTML = `
        <div>${message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;

      alertPlaceholder.append(wrapper);

      // Удаление алерта через 10 секунд
      setTimeout(() => {
        wrapper.classList.remove("show"); // плавное исчезновение
        setTimeout(() => wrapper.remove(), 150); // удаление из DOM
      }, 10000);
    };