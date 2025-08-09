import { createNode } from "../../helpers/dom";
import UiProductSlide from "../slides/ui-product-slide";

export default class UiFeaturedProductsSection {
    constructor(name, title, products) {
        this.name = name;
        this.title = title;
        this.products = products;

        return this.draw();
    }

    draw() {
        const container = document.createElement('section');

        if (this.products.length) {
            container.setAttribute('class', this.title.style == 1 ? 'ui-featured-on-sale-products-section l-featured-on-sale-products-section' : 'ui-featured-best-seller-products-section l-featured-best-seller-products-section');
            container.append(this.drawTitle(), this.drawProducts());
        }

        return container;
    }

    drawTitle() {
        let titleNode = {};
        switch (this.title.style) {
            case 1:
                titleNode = createNode(`
                    <div class="l-container l-container--phone-spacing l-container--position-relative">
                        <div class="ui-section-title ui-section-title--style-1 l-section-title l-flex-row l-flex-row--align-center l-flex-row--spacing-0">
                            <div class="l-flex-col-8">
                                <h2 class="l-section-title-title ui-section-title-title">${this.title.title}<span class="ui-section-title-subtitle">${this.title.subTitle ? this.title.subTitle : ''}</span></h2>
                            </div>
                            <div class="l-flex-col-4 ui-section-title-action-container">
                                ${this.title.actionBtn ? `
                                <a class="l-section-title-action ui-section-title-action" href="${this.title.actionBtn.link}">
                                    <span>${this.title.actionBtn.text}</span>
                                    <span class="l-section-title-action-icon">
                                        <svg width="10" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2.5.5.737 2.263 6.462 8 .737 13.738 2.5 15.5 10 8 2.5.5Z" fill="#686868"></path>
                                        </svg>
                                    </span>
                                </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `);
                break;

            case 2:
                titleNode = createNode(`
                    <div class="l-container l-container--phone-spacing l-container--position-relative">
                        <div class="ui-section-title ui-section-title--style-2 l-section-title l-flex-row l-flex-row--align-center l-flex-row--spacing-0">
                            <div class="l-flex-col-8">
                                <h2 class="l-section-title-title ui-section-title-title ui-section-title-title--style-2 ui-section-title-title--color-success">${this.title.title}</h2>
                            </div>
                            <div class="l-flex-col-4 ui-section-title-action-container">
                                ${this.title.actionBtn ? `
                                <a class="ui-section-title-action ui-section-title-action--color-success" href="${this.title.actionBtn.link}">
                                    <span>${this.title.actionBtn.text}</span>
                                    <span class="l-section-title-action-icon">
                                        <svg width="10" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2.5.5.737 2.263 6.462 8 .737 13.738 2.5 15.5 10 8 2.5.5Z" fill="#686868"></path>
                                        </svg>
                                    </span>
                                </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `);
                break;

                case 3:
                    titleNode = createNode(`
                        <div class="l-container l-container--phone-spacing l-container--position-relative">
                            <div class="ui-section-title ui-section-title--style-1 l-section-title l-flex-row l-flex-row--align-center l-flex-row--spacing-0">
                                <div class="l-flex-col-8">
                                    <h2 class="l-section-title-title ui-section-title-title ui-section-title-title--color-success ui-section-title-title--style-3">
                                    ${this.title.icon == 'logo' ? `<svg viewBox="0 0 25 29" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="25" height="29" rx="5" fill="#37A446"></rect><path d="M8.38 9.201h-.126V15.854c0 .1-.057.304-.246.594-.18.276-.446.58-.774.864-.668.582-1.503 1.018-2.244 1.078l-.126.01.01.126.077.884.01.125.125-.01c1.082-.088 2.146-.687 2.922-1.361a5.45 5.45 0 0 0 .982-1.103c.238-.366.428-.79.428-1.207V9.201H8.38ZM11.723 12.843v-.126H10.56V19.453H11.723v-6.61Z" fill="#fff" stroke="#fff" stroke-width=".252"></path><path d="M18.775 13.433c0 1.886-1.573 3.42-3.52 3.42s-3.52-1.534-3.52-3.42 1.573-3.42 3.52-3.42 3.52 1.534 3.52 3.42Zm-3.52 4.56c2.583 0 4.684-2.038 4.684-4.56 0-2.521-2.1-4.56-4.684-4.56-2.583 0-4.683 2.039-4.683 4.56 0 2.521 2.1 4.56 4.683 4.56Z" fill="#fff" stroke="#fff" stroke-width=".252"></path></svg>` : ''}
                                    ${this.title.title}
                                    <span class="ui-section-title-subtitle">${this.title.subTitle ? this.title.subTitle : ''}</span>
                                    </h2>
                                </div>
                                <div class="l-flex-col-4 ui-section-title-action-container">
                                    ${this.title.actionBtn ? `
                                    <a class="l-section-title-action ui-section-title-action" href="${this.title.actionBtn.link}">
                                        <span>${this.title.actionBtn.text}</span>
                                        <span class="l-section-title-action-icon">
                                            <svg width="10" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.5.5.737 2.263 6.462 8 .737 13.738 2.5 15.5 10 8 2.5.5Z" fill="#686868"></path>
                                            </svg>
                                        </span>
                                    </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `);
                    break;

                case 4:
                    titleNode = createNode(`
                        <div class="l-container l-container--phone-spacing l-container--position-relative">
                            <div class="ui-section-title ui-section-title--style-1 l-section-title l-flex-row l-flex-row--align-center l-flex-row--spacing-0">
                                <div class="l-flex-col-8">
                                    <h2 class="l-section-title-title ui-section-title-title ui-section-title-title--bold ui-section-title-title--color-primary">
                                    ${this.title.title} <span class="ui-section-title-subtitle">${this.title.subTitle ? this.title.subTitle : ''}</span>
                                    </h2>
                                </div>
                                <div class="l-flex-col-4 ui-section-title-action-container">
                                    ${this.title.actionBtn ? `
                                    <a class="l-section-title-action ui-section-title-action" href="${this.title.actionBtn.link}">
                                        <span>${this.title.actionBtn.text}</span>
                                        <span class="l-section-title-action-icon">
                                            <svg width="10" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.5.5.737 2.263 6.462 8 .737 13.738 2.5 15.5 10 8 2.5.5Z" fill="#686868"></path>
                                            </svg>
                                        </span>
                                    </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `);
                    break;

                case 5:
                        titleNode = createNode(`
                            <div class="l-container l-container--phone-spacing l-container--position-relative">
                                <div class="ui-section-title ui-section-title--style-1 l-section-title l-flex-row l-flex-row--align-center l-flex-row--spacing-0">
                                    <div class="l-flex-col-8">
                                        <h2 class="l-section-title-title ui-section-title-title ui-section-title-title--style-5">${this.title.title}<span class="ui-section-title-subtitle">${this.title.subTitle ? this.title.subTitle : ''}</span></h2>
                                    </div>
                                    <div class="l-flex-col-4 ui-section-title-action-container">
                                        ${this.title.actionBtn ? `
                                        <a class="l-section-title-action ui-section-title-action" href="${this.title.actionBtn.link}">
                                            <span>${this.title.actionBtn.text}</span>
                                        </a>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `);
                        break;
            default:
                break;
        }

        return titleNode;
    }

    drawProducts() {
        let productsNode = createNode(`
        <div class="l-container l-container--phone-spacing l-container--position-relative">
            <div class="swiper l-slider-row" data-l-slider="${this.name}">
                <div class="l-flex-row l-flex-row--nowrap l-flex-row--spacing-0 swiper-wrapper" data-products-row="true">
                </div>
            </div>
            <div class="swiper-pagination" data-l-slider-pagination="${this.name}"></div>
            <div class="swiper-button-prev" data-l-slider-navigation-prev="${this.name}"></div>
            <div class="swiper-button-next" data-l-slider-navigation-next="${this.name}"></div>
        </div>
        `);

        let productsRowNode = productsNode.querySelector('[data-products-row]');

        this.products.forEach(product => {
            let productNode = createNode(`<div class="l-flex-col-lg-3 swiper-slide"></div>`);
            let productSlide = new UiProductSlide(product);
            productNode.firstChild.appendChild(productSlide);
            productsRowNode.appendChild(productNode);
        });

        return productsNode;
    }
}