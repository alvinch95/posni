{{-- Page styles of the Produk Website form. Included from create/edit into the layout's <head>. --}}
<style>
    /* ID | EN tabs: dot on the EN tab while an English field has a value */
    .lang-dot {
        display: inline-block;
        width: 0.5rem;
        height: 0.5rem;
        margin-left: 0.4rem;
        border-radius: 50%;
        background-color: var(--primary-color, #8c9c84);
    }

    .product-form .nav-tabs .nav-link {
        color: var(--text-muted, #8b7e74);
    }

    .product-form .nav-tabs .nav-link.active {
        color: var(--text-dark, #4a4036);
        font-weight: 600;
    }

    /* "Pengaturan lanjutan" toggle */
    .product-form .advanced-toggle {
        min-height: 44px;
        color: var(--text-dark, #4a4036);
        font-weight: 500;
        text-decoration: none;
    }

    .product-form .advanced-toggle .bi {
        display: inline-block;
        margin-right: 0.35rem;
        transition: transform 0.2s ease;
    }

    .product-form .advanced-toggle[aria-expanded="true"] .bi {
        transform: rotate(180deg);
    }

    /* Sidebar column: one column on phones (DOM order is the phone order), sticky on lg and up */
    .product-form-side > .card {
        flex-shrink: 0;
    }

    .product-form .category-list {
        max-height: 18rem;
        overflow-y: auto;
    }

    .product-form .price-preview-value {
        font-variant-numeric: tabular-nums;
        white-space: nowrap; /* "Rp 89.500" wraps as a whole, never in the middle */
    }

    /* The two price panels get narrow next to the sidebar around 1024px: keep the whole placeholder visible */
    .product-form .hamper-option .input-group-text {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
    }

    .product-form .hamper-option .price-input {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
    }

    /* Desktop widths only: on phones inputs stay 16px (iOS zooms into smaller ones on focus) */
    @media (min-width: 992px) and (max-width: 1299.98px) {
        .product-form .hamper-option .price-input {
            font-size: 0.9rem;
        }
    }

    @media (min-width: 992px) {
        .product-form-side {
            position: sticky;
            top: 1rem;
            align-self: flex-start;
            max-height: calc(100vh - 2rem); /* taller than the screen: the sidebar scrolls on its own */
            overflow-y: auto;
            scrollbar-width: thin;
            padding-bottom: 2px;
        }
    }

    /* Phone and tablet: fixed Simpan bar. Its z-index (1020) is under the phone sidebar panel (1050) and the
       header (1060) from modern.css, so the open menu always covers it. From 768px the sidebar is a fixed
       column on the left, so the bar starts after it. */
    .product-form {
        padding-bottom: 6rem;
    }

    .product-save-bar {
        position: fixed;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1020;
        padding: 0.5rem 1rem;
        padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px));
        background: #fff;
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 12px rgba(74, 64, 54, 0.08);
    }

    @media (min-width: 768px) {
        .product-save-bar {
            left: var(--sidebar-width, 260px);
        }

        html.sidebar-mini .product-save-bar {
            left: var(--sidebar-mini-width, 72px);
        }
    }

    @media (min-width: 992px) {
        .product-form {
            padding-bottom: 3rem;
        }
    }
</style>
