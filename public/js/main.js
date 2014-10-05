(function () {
    var updatedEl = document.querySelector('.js-updated-date'),
        updatedDate = updatedEl.getAttribute('datetime');

    updatedEl.textContent = moment(updatedDate, 'YYYY-MM-DD HH:mmZ').fromNow();
    updatedEl.setAttribute('title', moment(updatedDate).format('MMMM D, YYYY HH:mm'));

    setInterval(function () {
        updatedEl.textContent = moment(updatedDate, 'YYYY-MM-DD HH:mmZ').fromNow();
    }, 30000);
}());
