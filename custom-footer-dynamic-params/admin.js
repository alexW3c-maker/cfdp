document.addEventListener('DOMContentLoaded', function() {
    // Кнопка Добавить поле
    var addRowBtn = document.getElementById('cfdp-add-row');
    var tableBody = document.querySelector('#cfdp-fields-table tbody');

    // Функция автоформатирования номера телефона
    function formatPhone(value) {
        var digits = value.replace(/\D/g, '').substr(0, 11); // максимум 11 цифр
        var parts = [];
        if (digits.length > 0) parts.push('(' + digits.substr(0,3));
        if (digits.length >= 3) parts[0] += ')';
        if (digits.length > 3) parts.push(digits.substr(3,3));
        if (digits.length > 6) parts.push(digits.substr(6,3));
        if (digits.length > 9) parts.push(digits.substr(9,2));
        var formatted = '';
        if (parts.length > 0) formatted = parts[0];
        if (parts.length > 1) formatted += '-' + parts[1];
        if (parts.length > 2) formatted += '-' + parts[2];
        if (parts.length > 3) formatted += '-' + parts[3];
        return formatted;
    }

    // Навешиваем обработчик на все текущие поля типа телефон
    function bindPhoneMask(input) {
        input.addEventListener('input', function(e) {
            var pos = input.selectionStart;
            var oldLength = input.value.length;
            input.value = formatPhone(input.value);
            var newLength = input.value.length;
            input.selectionEnd = input.selectionStart = pos + (newLength - oldLength);
        });
    }

    var rows = tableBody.querySelectorAll('tr');
    rows.forEach(function(row) {
        var select = row.querySelector('select.cfdp-type-select');
        var input = row.querySelector('input[name="cfdp_value[]"]');
        if (select && input && select.value === 'phone') {
            bindPhoneMask(input);
        }
    });

    // Добавление новой строки
    if (addRowBtn && tableBody) {
        addRowBtn.addEventListener('click', function() {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="text" name="cfdp_label[]" value="" class="regular-text" required></td>' +
                '<td><select name="cfdp_type[]" class="cfdp-type-select">' +
                    '<option value="text">Текст</option>' +
                    '<option value="phone">Телефон</option>' +
                    '<option value="email">Email</option>' +
                '</select></td>' +
                '<td>' +
                    '<input type="text" name="cfdp_value[]" value="" class="regular-text">' +
                '</td>' +
                '<td><button type="button" class="button cfdp-remove-row">Удалить</button></td>';
            tableBody.appendChild(tr);
        });
    }

    // Удаление строки по кнопке Удалить
    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('cfdp-remove-row')) {
            var tr = event.target.closest('tr');
            if (tr) tr.remove();
        }
    });

    // Смена типа поля и динамическая маска
    document.addEventListener('change', function(event) {
        if (event.target && event.target.classList.contains('cfdp-type-select')) {
            var td = event.target.parentNode.nextElementSibling;
            var input = td.querySelector('input[name="cfdp_value[]"]');
            var hint = td.querySelector('.cfdp-phone-hint');
            if (hint) hint.remove();
            if (event.target.value === 'phone') {
                input.setAttribute('placeholder', '(000)-000-000-00');
                var hintDiv = document.createElement('div');
                hintDiv.className = 'cfdp-phone-hint';
                hintDiv.style.color = '#888';
                hintDiv.style.fontSize = '12px';
                hintDiv.textContent = 'Формат: (000)-000-000-00';
                td.appendChild(hintDiv);
                bindPhoneMask(input);
            } else {
                input.removeAttribute('placeholder');
            }
        }
    });

    document.querySelectorAll('select.cfdp-type-select').forEach(function(select) {
        if (select.value === 'phone') {
            var td = select.parentNode.nextElementSibling;
            var input = td.querySelector('input[name="cfdp_value[]"]');
            if (input) bindPhoneMask(input);
        }
    });
});