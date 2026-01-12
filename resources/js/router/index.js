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
const Register = () => import("../pages/Register.vue"); // Asegúrate de tener este componente o quita la ruta
const ForgotPassword = () => import("../pages/ForgotPassword.vue");
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

// --- ADMIN (Componentes que crearemos) ---
const AdminLayout = () => import("../components/layout/AdminLayout.vue");
const AdminDashboard = () => import("../pages/admin/Dashboard.vue");
const AdminProducts = () => import("../pages/admin/ProductList.vue");
const AdminProductForm = () => import("../pages/admin/ProductForm.vue");
const AdminUsers = () => import("../pages/admin/UserList.vue");
const AdminOrders = () => import("../pages/admin/OrderList.vue");
const AdminReviews = () => import("../pages/admin/ReviewList.vue");


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

        // Usuario (Protegidas basicas)
        { path: "/wishlist", name: "wishlist", component: Wishlist, meta: { requiresAuth: true } },
        { path: "/orders", name: "orders", component: Orders, meta: { requiresAuth: true } },
        { path: "/pago/resultado", name: "paymentResult", component: PaymentResult, meta: { requiresAuth: true } },

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
                    path: "dashboard", // /admin/dashboard
                    name: "admin-dashboard",
                    component: AdminDashboard
                },
                {
                    path: "products", // /admin/products
                    name: "admin-products",
                    component: AdminProducts
                },
                {
                    path: "products/create",  // /admin/products/create
                    name: "admin-product-create",
                    component: AdminProductForm
                },
                {
                    path: "products/:id/edit", // /admin/products/edit
                    name: "admin-product-edit",
                    component: AdminProductForm,
                    props: true
                },
                {
                    path: "users", // /admin/users
                    name: "admin-users",
                    component: AdminUsers
                },
                {
                    path: "orders", // /admin/orders
                    name: "admin-orders",
                    component: AdminOrders
                },
                {
                    path: "reviews", // /admin/reviews
                    name: "admin-reviews",
                    component: AdminReviews
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
// GUARDIA DE NAVEGACIÓN
// ============================
router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    // 1. Si la ruta requiere auth y no tenemos usuario cargado, intentar cargarlo
    if (to.meta.requiresAuth && auth.user === null) {
        await auth.fetchUser();
    }

    // 2. Comprobar si está autenticado para rutas protegidas
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({
            name: "login",
            query: { redirect: to.fullPath },
        });
    }

    // 3. Lógica específica de ADMIN
    if (to.meta.requiresAdmin) {
        // Si no es admin (o no existe user), redirigir a home o login
        if (!auth.user?.is_admin) {
            // Opción A: Redirigir a Home (silencioso)
            return next({ name: "home" });

            // Opción B: Podrías redirigir a una página de "No autorizado"
            // return next({ name: 'unauthorized' });
        }
    }

    // Si pasa todas las comprobaciones, adelante
    next();
});

export default router;
