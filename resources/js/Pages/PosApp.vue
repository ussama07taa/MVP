<template>
  <ToastNotification />
  <div class="h-screen flex flex-col lg:flex-row bg-[#FAFAF9] font-sans overflow-hidden selection:bg-amber-500 selection:text-white">

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- LEFT: Products                                              -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <div class="print:hidden flex-1 flex flex-col min-h-0 relative bg-[#FAFAF9]">

      <!-- Premium Hero Header -->
      <header class="shrink-0 bg-white/70 backdrop-blur-2xl border-b border-slate-200/50 px-4 sm:px-8 py-4 z-20 flex items-center gap-4 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">

        <!-- Logo mark -->
        <div class="w-11 h-11 bg-slate-950 rounded-2xl flex items-center justify-center shadow-2xl shadow-slate-900/10 shrink-0 group hover:scale-105 transition-transform cursor-pointer">
          <div class="w-5 h-5 bg-amber-500 rounded-lg blur-[8px] absolute opacity-0 group-hover:opacity-100 transition-opacity"></div>
          <LayoutDashboardIcon class="w-5 h-5 text-amber-500 relative z-10"/>
        </div>

        <!-- Title -->
        <div class="hidden sm:block shrink-0">
          <h1 class="text-lg font-black text-slate-950 tracking-tighter leading-none flex items-center gap-2">
            POS Terminal
            <span class="px-2 py-0.5 bg-amber-500 text-[10px] font-black text-slate-950 rounded-lg uppercase tracking-widest animate-pulse">Pro</span>
          </h1>
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1">{{ companyName }}</p>
        </div>

        <!-- Search bar -->
        <div class="flex-1 relative group max-w-xl mx-auto">
          <SearchIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-600 transition-colors" />
          <input type="text" v-model="searchQuery" placeholder="Rechercher un produit ou service..." 
            class="w-full pl-11 pr-4 py-3 bg-slate-100/50 border border-transparent rounded-2xl focus:outline-none focus:bg-white focus:border-amber-500/30 focus:ring-4 focus:ring-amber-500/5 font-bold text-sm text-slate-800 transition-all placeholder:text-slate-400">
        </div>

        <!-- Desktop actions -->
        <div class="hidden lg:flex items-center gap-3 shrink-0">
          <Link v-if="authUser?.role === 'admin' || authUser?.role === 'cashier'" href="/admin/dashboard"
            class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-slate-600 hover:text-slate-950 bg-white border border-slate-200/60 hover:border-slate-400 px-4 py-2.5 rounded-2xl transition-all shadow-sm">
            <LayoutDashboardIcon class="w-4 h-4"/> Dashboard
          </Link>
          <button @click="logout"
            class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-white bg-slate-950 hover:bg-slate-800 px-4 py-2.5 rounded-2xl transition-all shadow-xl shadow-slate-900/10">
            Quitter
          </button>
        </div>

        <!-- Mobile: hamburger -->
        <button @click="showMobileMenu = !showMobileMenu"
          class="lg:hidden w-11 h-11 flex items-center justify-center bg-slate-100 rounded-2xl border border-slate-200 shrink-0">
          <MenuIcon class="w-5 h-5 text-slate-700" />
        </button>
      </header>

      <!-- Mobile dropdown menu -->
      <Transition name="slide-down">
        <div v-if="showMobileMenu" class="lg:hidden absolute inset-x-0 top-[76px] z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200 shadow-2xl px-6 py-4 flex flex-col gap-3">
          <Link v-if="authUser?.role === 'admin' || authUser?.role === 'cashier'" href="/admin/dashboard"
            class="flex items-center gap-3 text-sm font-black text-slate-800 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200">
            <LayoutDashboardIcon class="w-5 h-5 text-amber-600"/> Dashboard
          </Link>
          <button @click="logout"
            class="flex items-center gap-3 text-sm font-black text-rose-600 px-4 py-3 rounded-2xl bg-rose-50 border border-rose-100 w-full text-left">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
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

    <!-- Desktop sidebar (always visible) — Narrower for better grid focus -->
    <div class="print:hidden hidden lg:flex lg:w-[300px] xl:w-[320px] shrink-0 border-l border-slate-200/50 bg-white">
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
    <div v-if="showClientModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-[100] flex items-center justify-center p-4">
      <div class="bg-white rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-[0_40px_100px_rgba(0,0,0,0.5)] border border-slate-100 scale-in-center relative">
        <div class="absolute inset-x-0 -top-px h-1 bg-gradient-to-r from-transparent via-amber-500/40 to-transparent"></div>
        
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div>
            <h3 class="font-black text-2xl text-slate-900 tracking-tight">Nouveau Client</h3>
            <p class="text-[10px] font-black text-amber-600 mt-1 uppercase tracking-[0.25em]">Enregistrement Rapide</p>
          </div>
          <button @click="showClientModal = false" class="w-12 h-12 flex items-center justify-center bg-white rounded-2xl text-slate-400 hover:text-slate-950 shadow-sm border border-slate-200 transition-all active:scale-95">
            <XIcon class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-8 space-y-6">
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5 ml-1">Identité Complète *</label>
            <div class="relative group">
               <UserIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-amber-500 transition-colors" />
               <input type="text" v-model="newClient.name" 
                 class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent focus:bg-white rounded-[1.25rem] focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500/30 font-black text-sm text-slate-900 transition-all placeholder:text-slate-300" 
                 placeholder="Ex: Hassan El Alami">
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5 ml-1">Contact Téléphonique</label>
            <div class="relative group">
               <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
               <input type="text" v-model="newClient.phone" 
                 class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent focus:bg-white rounded-[1.25rem] focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500/30 font-black text-sm text-slate-900 transition-all placeholder:text-slate-300" 
                 placeholder="06... ou 05...">
            </div>
          </div>
        </div>
        
        <div class="px-8 pb-8 flex items-center gap-4">
          <button @click="showClientModal = false" class="flex-1 py-4 font-black text-slate-500 hover:text-slate-950 transition-colors text-[10px] uppercase tracking-widest bg-slate-50 rounded-2xl">Annuler</button>
          <button @click="saveNewClient" :disabled="!newClient.name || isSubmittingClient" 
            class="flex-[2] py-4 font-black text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-2xl transition-all shadow-xl shadow-amber-500/20 disabled:opacity-30 text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
            <Loader2Icon v-if="isSubmittingClient" class="w-4 h-4 animate-spin" />
            Créer le Client
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
import { LayoutDashboardIcon, SearchIcon, XIcon, Loader2Icon, MenuIcon, ShoppingCartIcon, ChevronDownIcon, UserIcon } from 'lucide-vue-next';

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
    const res = await axios.post('/api/admin/clients', newClient.value);
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
