import '../../_components/main/footer/footer';
import '../../_components/layout/images/l-lazyload-img';
import '../../_components/full/form/form-validate';
import '../../_components/full/form/form-input';

const mediaQuery = window.matchMedia("(max-width: 1200px)");
const sidebar = document.querySelector('[data-profile-sidebar]');
const card = document.querySelector('[data-profile-card]');
const onMediaQueryChange = (mediaQuery) => {
    if (mediaQuery.matches) {
        sidebar.style.display = 'none';
        card.style.display = 'block';
    } else {
        sidebar.style.display = 'block';
        card.style.display = 'block';
    }
}
onMediaQueryChange(mediaQuery);
mediaQuery.addListener(onMediaQueryChange);

document.querySelectorAll('[data-profile-sidebar-btn]').forEach(sidebarBtn => {
    sidebarBtn.addEventListener('click', (e) => {
        if (mediaQuery.matches && sidebarBtn.classList.contains('active')) {
            e.preventDefault();
            sidebar.style.display = 'none';
            card.style.display = 'block';
        }
    });
});

document.querySelectorAll('[data-profile-card-btn]').forEach(cardBtn => {
    cardBtn.addEventListener('click', () => {
        if (mediaQuery.matches) {
            sidebar.style.display = 'block';
            card.style.display = 'none';
        }
    });
});