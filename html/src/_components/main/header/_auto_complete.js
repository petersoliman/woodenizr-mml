import LayoutFormDatalist from "../../layout/forms/l-form-datalist";
import axios from "axios";
import LayoutToast from "../../layout/toasts/l-toast";
import {debounce} from "../../helpers/requests";

const searchDatalistInput = document.querySelector('[data-header-topbar-searchbar-input]');
if (searchDatalistInput) {
    const clearBtn = document.querySelector('[data-header-topbar-searchbar-input-clear-btn]');
    const searchDatalist = new LayoutFormDatalist(searchDatalistInput);

    searchDatalist.onInput = debounce((instance) => {
        const _token = document.querySelector('[data-header-topbar-searchbar-token-input]').value;
        const string = instance.input.value.trim();
        if (string.length < 4) {
            return;
        }

        axios.get(instance.input.dataset.headerTopbarSearchbarInputUrl, {
            params: {
                str: string,
                _token
            }
        }).then(function (response) {
            instance.dropdownItems.classList.remove('loading');

            if (!response.data.error) {
                let html = "";
                response.data.products.forEach((item) => {
                    html += `<a href="${item.absoluteUrl}" class="ui-form-datalist-dropdown-menu-item l-form-datalist-dropdown-menu-item">${item.title}</a>`
                });
                instance.dropdownItems.innerHTML = html;
            } else {
                new LayoutToast(response.data.message, 'error');
            }
        }).catch(function (error) {
            instance.dropdownItems.classList.remove('loading');

            instance.dropdownItems.innerHTML = '';
            new LayoutToast(error.message, 'error');
        });
        if (instance.input.value) {
            instance.show();
        } else {
            instance.hide();
        }

    }, 200);

    searchDatalistInput.addEventListener('input', () => {
        if (searchDatalistInput.value) {
            clearBtn.style.display = 'inline-block';
        } else {
            clearBtn.style.display = 'none';
        }
    });

    clearBtn.addEventListener('click', () => {
        searchDatalistInput.value = '';
        clearBtn.style.display = 'none';
    });

    searchDatalist.bindEvents();
}