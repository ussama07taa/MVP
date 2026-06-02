<template>
  <ToastNotification />
  <div class="h-screen flex flex-col lg:flex-row bg-slate-50 font-sans overflow-hidden selection:bg-brand-500 selection:text-white">

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- LEFT: Products                                              -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div class="print:hidden flex-1 flex flex-col min-h-0 relative bg-slate-50">

      <!-- Compact Glassmorphic Header -->
      <header class="shrink-0 bg-white/90 backdrop-blur-xl border-b border-slate-200/60 px-4 sm:px-6 py-3 z-20 flex items-center gap-3 shadow-sm">

        <!-- Logo mark -->
        <div class="w-9 h-9 bg-gradient-to-br from-slate-900 to-slate-700 rounded-xl flex items-center justify-center shadow shadow-slate-900/20 shrink-0">
          <LayoutDashboardIcon class="w-4 h-4 text-white"/>
        </div>

        <!-- Title (hidden on very small phones) -->
        <div class="hidden sm:block shrink-0">
          <h1 class="text-base font-black text-slate-900 tracking-tight leading-none flex items-center gap-1.5">
            Point de Vente
            <span class="px-1.5 py-0.5 bg-emerald-50 border border-emerald-200 text-[9px] font-black text-emerald-600 rounded-md uppercase tracking-wider">Live</span>
          </h1>
          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ companyName }}</p>
        </div>

        <!-- Search bar -->
        <div class="flex-1 relative group">
          <SearchIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors" />
          <input type="text" v-model="searchQuery" placeholder="Chercher un produit..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 font-bold text-sm text-slate-700 transition-all shadow-sm">
        </div>

        <!-- Desktop actions -->
        <div class="hidden lg:flex items-center gap-2 shrink-0">
          <Link v-if="authUser?.role === 'admin' || authUser?.role === 'cashier'" href="/admin/dashboard"
            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:border-slate-300 px-3 py-2 rounded-xl transition-all">
            <LayoutDashboardIcon class="w-3.5 h-3.5"/> Dashboard
          </Link>
          <button @click="logout"
            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 px-3 py-2 rounded-xl transition-all">
            Déconnexion
          </button>
        </div>

        <!-- Mobile: hamburger -->
        <button @click="showMobileMenu = !showMobileMenu"
          class="lg:hidden w-9 h-9 flex items-center justify-center bg-slate-100 rounded-xl border border-slate-200 shrink-0">
          <MenuIcon class="w-4 h-4 text-slate-600" />
        </button>
      </header>

      <!-- Mobile dropdown menu -->
      <Transition name="slide-down">
        <div v-if="showMobileMenu" class="lg:hidden absolute inset-x-0 top-[57px] z-40 bg-white border-b border-slate-200 shadow-lg px-4 py-3 flex flex-col gap-2">
          <Link v-if="authUser?.role === 'admin' || authUser?.role === 'cashier'" href="/admin/dashboard"
            class="flex items-center gap-2 text-sm font-black text-slate-700 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200">
            <LayoutDashboardIcon class="w-4 h-4"/> Dashboard
          </Link>
          <button @click="logout"
            class="flex items-center gap-2 text-sm font-black text-red-600 px-3 py-2.5 rounded-xl bg-red-50 border border-red-100 w-full text-left">
            Déconnexion
          </button>
        </div>
      </Transition>

      <!-- Products Grid -->
      <ProductGrid
        v-model:selectedCategory="selectedCategory"
        :isLoading="isLoading"
        :searchQuery="searchQuery"
        :services="services"
        :panels="panels"
        :cantos="cantos"
        :consumables="consumables"
      />
    </div>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- RIGHT: Cart — Desktop sidebar / Mobile drawer              -->
    <!-- ═══════════════════════════════════════════════════════════ -->

    <!-- Desktop sidebar (always visible) -->
    <div class="print:hidden hidden lg:flex lg:w-[340px] xl:w-[360px] 2xl:w-[400px] shrink-0 border-l border-slate-200/80">
      <CartSidebar :clients="clients" @openClientModal="showClientModal = true" @orderSubmitted="handleOrderSubmitted" />
    </div>

    <!-- Mobile: Floating Cart FAB -->
    <Transition name="fab-pop">
      <button v-if="cartCount > 0" @click="showMobileCart = true"
        class="lg:hidden fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl shadow-slate-900/40 active:scale-95 transition-all">
        <ShoppingCartIcon class="w-5 h-5" />
        <span class="text-sm font-black">Panier</span>
        <span class="bg-emerald-400 text-slate-900 text-[10px] font-black px-2 py-0.5 rounded-full min-w-[20px] text-center leading-tight">{{ cartCount }}</span>
      </button>
    </Transition>

    <!-- Mobile: Cart Drawer -->
    <Transition name="slide-up">
      <div v-if="showMobileCart" class="lg:hidden fixed inset-0 z-[60]">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="showMobileCart = false"></div>
        <!-- Drawer -->
        <div class="absolute inset-x-0 bottom-0 h-[92vh] bg-slate-50 rounded-t-3xl overflow-hidden shadow-2xl flex flex-col">
          <!-- Handle bar -->
          <div class="shrink-0 flex items-center justify-between px-5 py-3 border-b border-slate-100 bg-white/80 backdrop-blur-sm">
            <button @click="showMobileCart = false"
              class="flex items-center gap-1.5 text-[10px] font-black text-slate-500 uppercase tracking-wider">
              <ChevronDownIcon class="w-4 h-4" /> Fermer
            </button>
            <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Panier ({{ cartCount }})</span>
          </div>
          <CartSidebar :clients="clients" @openClientModal="showClientModal = true" @orderSubmitted="handleOrderSubmittedMobile" class="flex-1 min-h-0 overflow-hidden" />
        </div>
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- QUICK ADD CLIENT MODAL                                      -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div v-if="showClientModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-[100] flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border border-slate-100 scale-in-center">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
          <div>
            <h3 class="font-black text-xl text-slate-900">Nouveau Client</h3>
            <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">Ajout rapide</p>
          </div>
          <button @click="showClientModal = false" class="w-9 h-9 flex items-center justify-center bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 hover:bg-slate-200 transition-all">
            <XIcon class="w-4 h-4" />
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nom complet *</label>
            <input type="text" v-model="newClient.name" class="w-full p-3.5 bg-slate-50 border border-slate-200 focus:bg-white rounded-xl focus:ring-2 focus:ring-brand-500/10 focus:border-brand-500 font-bold transition-all" placeholder="Ex: Hassan Najjar">
          </div>
          <div>
            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Téléphone</label>
            <input type="text" v-model="newClient.phone" class="w-full p-3.5 bg-slate-50 border border-slate-200 focus:bg-white rounded-xl focus:ring-2 focus:ring-brand-500/10 focus:border-brand-500 font-bold transition-all" placeholder="06...">
          </div>
        </div>
        <div class="px-6 pb-6 flex justify-end gap-3">
          <button @click="showClientModal = false" class="px-5 py-2.5 font-black text-slate-500 hover:text-slate-800 transition-colors text-[10px] uppercase tracking-widest">Annuler</button>
          <button @click="saveNewClient" :disabled="!newClient.name || isSubmittingClient" class="px-6 py-2.5 font-black text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition-all shadow-md disabled:opacity-50 text-[10px] uppercase tracking-widest flex items-center gap-2">
            <Loader2Icon v-if="isSubmittingClient" class="w-3.5 h-3.5 animate-spin" />
            Enregistrer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { usePage, router, Link } from '@inertiajs/vue3';
import { useCartStore } from '@/stores/cart';
import { useToast } from '@/composables/useToast';
import { LayoutDashboardIcon, SearchIcon, XIcon, Loader2Icon, MenuIcon, ShoppingCartIcon, ChevronDownIcon } from 'lucide-vue-next';

import ProductGrid from '@/Components/Pos/ProductGrid.vue';
import CartSidebar from '@/Components/Pos/CartSidebar.vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const cartStore = useCartStore();
const toast = useToast();
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const companyName = computed(() => (page.props.settings || {}).company_name || 'Mon Entreprise');
const logout = () => router.post('/logout');

const props = defineProps({
  initialClients: Array,
  initialServices: Array,
  initialPanels: Array,
  initialCantos: Array,
  initialConsumables: Array
});

const clients = computed(() => props.initialClients);
const services = computed(() => props.initialServices);
const panels = computed(() => props.initialPanels);
const cantos = computed(() => props.initialCantos);
const consumables = computed(() => props.initialConsumables);
const isLoading = ref(false);

const searchQuery = ref('');
const selectedCategory = ref('all');

const showMobileMenu = ref(false);
const showMobileCart = ref(false);

const cartCount = computed(() => cartStore.cart.length);

onMounted(() => {
  cartStore.services = props.initialServices;
  cartStore.panels = props.initialPanels;
  cartStore.cantos = props.initialCantos;
});

const showClientModal = ref(false);
const isSubmittingClient = ref(false);
const newClient = ref({ name: '', phone: '' });

const saveNewClient = async () => {
  if (!newClient.value.name) return;
  isSubmittingClient.value = true;
  try {
    const res = await axios.post('/api/clients', newClient.value);
    router.reload({
      only: ['initialClients'],
      onSuccess: () => {
        cartStore.selectedClient = res.data.id;
        showClientModal.value = false;
        newClient.value = { name: '', phone: '' };
      }
    });
  } catch (error) {
    toast.error("Erreur lors de l'ajout du client.");
  } finally {
    isSubmittingClient.value = false;
  }
};

const handleOrderSubmitted = () => {};
const handleOrderSubmittedMobile = () => {
  showMobileCart.value = false;
};
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }

/* Mobile drawer animation */
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.35s cubic-bezier(0.32, 0.72, 0, 1); }
.slide-up-enter-from .absolute.inset-x-0, .slide-up-leave-to .absolute.inset-x-0 { transform: translateY(100%); }
.slide-up-enter-from .absolute.inset-0, .slide-up-leave-to .absolute.inset-0 { opacity: 0; }

/* Mobile menu dropdown */
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

/* FAB button pop */
.fab-pop-enter-active, .fab-pop-leave-active { transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
.fab-pop-enter-from, .fab-pop-leave-to { opacity: 0; transform: scale(0.7) translateY(10px); }

/* Modal animation */
.scale-in-center { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes scaleIn {
  from { transform: scale(0.88); opacity: 0; }
  to   { transform: scale(1);    opacity: 1; }
}
</style>
