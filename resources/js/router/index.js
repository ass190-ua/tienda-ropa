import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";


const Home = () => import("../pages/Home.vue");
const Destacados = () => import("../pages/Destacados.vue");
const Search = () => import("../pages/Search.vue");
const Shop = () => import("../pages/Shop.vue")
const ProductDetail = () => import("../pages/ProductDetail.vue");
const Cart = () => import("../pages/Cart.vue");
const Login = () => import("../pages/Login.vue");
const Novedades = () => import("../pages/Novedades.vue")
const Contacto = () => import("../pages/Contacto.vue");
const SobreNosotros = () => import("../pages/SobreNosotros.vue")
const Ayuda = () => import("../pages/Ayuda.vue")
const ForgotPassword = () => import("../pages/ForgotPassword.vue");

// opcionales (por si quieres ir dejándolos ya enlazados)
const Register = () => import("../pages/Register.vue");
const Wishlist = () => import("../pages/Wishlist.vue");
const Orders = () => import("../pages/Orders.vue");
const Terminos = () => import("../pages/Terminos.vue");
const Envios = () => import("../pages/Envios.vue");
const Devoluciones = () => import("../pages/Devoluciones.vue");
const NotFound = () => import("../pages/NotFound.vue");
const PaymentResult = () => import("../pages/PaymentResult.vue");

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: "/", name: "home", component: Home },
        { path: "/destacados", name: "destacados", component: Destacados },


        // búsqueda / catálogo
        { path: "/search", name: "search", component: Search },

        { path: "/shop", name: "shop", component: Shop },

        // detalle de producto
        { path: "/producto/:id", name: "product", component: ProductDetail, props: true },

        // flujo compra
        { path: "/cart", name: "cart", component: Cart },

        // auth
        { path: "/login", name: "login", component: Login },
        { path: "/register", name: "register", component: Register },
        { path: "/forgot-password", name: "ForgotPassword", component: ForgotPassword },

        // usuario
        { path: "/wishlist", name: "wishlist", component: Wishlist, meta: { requiresAuth: true } },
        { path: "/orders", name: "orders", component: Orders, meta: { requiresAuth: true } },

        // footer
        { path: '/novedades', component: Novedades },
        { path: '/sobre-nosotros', component: SobreNosotros },
        { path: '/ayuda', component: Ayuda },
        { path: "/contacto", name: "contacto", component: Contacto },
        { path: "/terminos", name: "terminos", component: Terminos },
        { path: "/envios", name: "envios", component: Envios },
        { path: "/devoluciones", name: "devoluciones", component: Devoluciones },

        { path: "/pago/resultado", name: "paymentResult", component: PaymentResult, meta: { requiresAuth: true } },

        // 404
        { path: "/:pathMatch(.*)*", name: "notfound", component: NotFound },
    ],
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (to.meta?.requiresAuth) {
        // Intentamos restaurar sesión solo cuando la ruta lo necesita
        if (auth.user === null) {
            await auth.fetchUser();
        }

        if (!auth.isAuthenticated) {
            return {
                name: "login",
                query: { redirect: to.fullPath },
            };
        }
    }
});


export default router;
