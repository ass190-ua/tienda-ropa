import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

// --- CLIENTE / PÚBLICO ---
const Home = () => import("../pages/Home.vue");
const Destacados = () => import("../pages/Destacados.vue");
const Search = () => import("../pages/Search.vue");
const Shop = () => import("../pages/Shop.vue");
const ProductDetail = () => import("../pages/ProductDetail.vue");
const Cart = () => import("../pages/Cart.vue");
const Login = () => import("../pages/Login.vue");
const Register = () => import("../pages/Register.vue");
const ForgotPassword = () => import("../pages/ForgotPassword.vue");
const ResetPassword = () => import("../pages/ResetPassword.vue");
const Novedades = () => import("../pages/Novedades.vue");
const Contacto = () => import("../pages/Contacto.vue");
const SobreNosotros = () => import("../pages/SobreNosotros.vue");
const Ayuda = () => import("../pages/Ayuda.vue");
const Wishlist = () => import("../pages/Wishlist.vue");
const Orders = () => import("../pages/Orders.vue");
const Terminos = () => import("../pages/Terminos.vue");
const Envios = () => import("../pages/Envios.vue");
const Devoluciones = () => import("../pages/Devoluciones.vue");
const NotFound = () => import("../pages/NotFound.vue");
const PaymentResult = () => import("../pages/PaymentResult.vue");
const Checkout = () => import("../pages/Checkout.vue");
const Profile = () => import("../pages/Profile.vue");

// --- ADMIN ---
const AdminLayout = () => import("../components/layout/AdminLayout.vue");
const AdminDashboard = () => import("../pages/admin/Dashboard.vue");
const AdminProducts = () => import("../pages/admin/ProductList.vue");
const AdminProductForm = () => import("../pages/admin/ProductForm.vue");
const AdminUsers = () => import("../pages/admin/UserList.vue");
const AdminOrders = () => import("../pages/admin/OrderList.vue");
const AdminReviews = () => import("../pages/admin/ReviewList.vue");
const AdminCoupons = () => import("../pages/admin/CouponList.vue");

const router = createRouter({
    history: createWebHistory(),
    routes: [
        // ============================
        // RUTAS PÚBLICAS / TIENDA
        // ============================
        { path: "/", name: "home", component: Home },
        { path: "/destacados", name: "destacados", component: Destacados },
        { path: "/search", name: "search", component: Search },
        { path: "/shop", name: "shop", component: Shop },
        { path: "/producto/:id", name: "product", component: ProductDetail, props: true },
        { path: "/cart", name: "cart", component: Cart },

        // Auth
        { path: "/login", name: "login", component: Login },
        { path: "/register", name: "register", component: Register },
        { path: "/forgot-password", name: "ForgotPassword", component: ForgotPassword },
        { path: "/reset-password", name: "ResetPassword", component: ResetPassword },

        // usuario
        { path: "/profile", name: "profile", component: Profile, meta: { requiresAuth: true } },
        { path: "/wishlist", name: "wishlist", component: Wishlist, meta: { requiresAuth: true } },
        { path: "/orders", name: "orders", component: Orders, meta: { requiresAuth: true } },

        // AQUÍ ESTABA EL PRIMER CONFLICTO: MANTENEMOS AMBAS
        { path: "/pago/resultado", name: "paymentResult", component: PaymentResult, meta: { requiresAuth: true } },
        { path: "/checkout", name: "checkout", component: Checkout, meta: { requiresAuth: true } },

        // Footer / Info
        { path: '/novedades', component: Novedades },
        { path: '/sobre-nosotros', component: SobreNosotros },
        { path: '/ayuda', component: Ayuda },
        { path: "/contacto", name: "contacto", component: Contacto },
        { path: "/terminos", name: "terminos", component: Terminos },
        { path: "/envios", name: "envios", component: Envios },
        { path: "/devoluciones", name: "devoluciones", component: Devoluciones },

        // ============================
        // RUTAS DE ADMINISTRADOR
        // ============================
        {
            path: "/admin",
            component: AdminLayout,
            meta: { requiresAuth: true, requiresAdmin: true },
            children: [
                {
                    path: "dashboard",
                    name: "admin-dashboard",
                    component: AdminDashboard
                },
                {
                    path: "products",
                    name: "admin-products",
                    component: AdminProducts
                },
                {
                    path: "products/create",
                    name: "admin-product-create",
                    component: AdminProductForm
                },
                {
                    path: "products/:id/edit",
                    name: "admin-product-edit",
                    component: AdminProductForm,
                    props: true
                },
                {
                    path: "users",
                    name: "admin-users",
                    component: AdminUsers
                },
                {
                    path: "orders",
                    name: "admin-orders",
                    component: AdminOrders
                },
                {
                    path: "reviews",
                    name: "admin-reviews",
                    component: AdminReviews
                },
                {
                    path: 'coupons',
                    name: 'AdminCoupons',
                    component: AdminCoupons
                }
            ]
        },

        // 404
        { path: "/:pathMatch(.*)*", name: "notfound", component: NotFound },
    ],
    scrollBehavior() {
        return { top: 0 };
    },
});

// ============================
// GUARDIA DE NAVEGACIÓN (FUSIONADA)
// ============================
router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    // 1. Cargar usuario si no existe (Lógica común)
    if (!auth.initialized) {
        try { await auth.fetchUser(); } catch (e) { }
    }

    // 2. Si vas a Login/Registro pero ya estás logueado -> Mandar a Home (Lógica de tus compañeros)
    if ((to.name === 'login' || to.name === 'register') && auth.isAuthenticated) {
        const redirect = to.query.redirect;
        if (typeof redirect === "string" && redirect.startsWith("/")) {
            return next(redirect);
        }
        return next({ name: "home" });
    }

    // 3. Rutas que requieren autenticación general (Wishlist, Checkout, etc.)
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({
            name: "login",
            query: { redirect: to.fullPath },
        });
    }

    // 4. Rutas de ADMIN (Tu lógica importante)
    if (to.meta.requiresAdmin) {
        if (!auth.user?.is_admin) {
            // Si intenta entrar a admin y no es admin -> Home
            return next({ name: "home" });
        }
    }

    // Si pasa todas las reglas, adelante
    next();
});

export default router;
