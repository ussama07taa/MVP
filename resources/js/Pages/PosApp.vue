<template>
  <ToastNotification />
  <div class="h-screen flex bg-slate-50 font-sans overflow-hidden selection:bg-brand-500 selection:text-white">
    
    <!-- LEFT PANEL: Products (Scrollable with premium dynamic backdrop) -->
    <div class="print:hidden w-full lg:w-[65%] xl:w-[70%] flex flex-col h-full relative z-10 bg-slate-50">
      
      <!-- Premium Glassmorphic Header -->
      <header class="bg-white/80 backdrop-blur-xl border-b border-slate-200/50 px-8 py-5 z-20 flex justify-between items-center sticky top-0 shadow-[0_2px_15px_rgba(15,23,42,0.015)]">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl flex items-center justify-center shadow-lg shadow-slate-950/10 border border-slate-700/10 transform hover:rotate-6 transition-all duration-300">
            <LayoutDashboardIcon class="w-5.5 h-5.5 text-white"/>
          </div>
          <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none mb-1 flex items-center">
              Point de Vente 
              <span class="ml-2.5 px-2 py-0.5 bg-brand-50 border border-brand-100/50 text-[10px] font-black text-brand-600 rounded-md uppercase tracking-wider">Live</span>
            </h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ companyName }}</p>
          </div>
        </div>

        <!-- Search Bar with dynamic focus glows -->
        <div class="hidden md:flex flex-1 max-w-md mx-8">
          <div class="relative w-full group">
            <SearchIcon class="w-5 h-5 absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors duration-300" />
            <input type="text" v-model="searchQuery" placeholder="Chercher un produit..." class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-bold text-slate-700 transition-all duration-300 shadow-sm">
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <!-- Back to Dashboard (Available to both Admin and Cashier) -->
          <Link v-if="authUser?.role === 'admin' || authUser?.role === 'cashier'" href="/admin/dashboard" class="group flex items-center text-xs font-black uppercase tracking-widest text-slate-700 hover:text-slate-900 bg-white border border-slate-200 hover:border-slate-300 shadow-sm hover:shadow-md px-5 py-3 rounded-2xl transition-all duration-300 transform active:scale-98">
            <LayoutDashboardIcon class="w-4 h-4 mr-2 text-slate-400 group-hover:text-brand-500 transition-colors duration-300"/> 
            Dashboard
          </Link>
          
          <!-- Logout Button -->
          <button @click="logout" class="group flex items-center text-xs font-black uppercase tracking-widest text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-100/50 px-5 py-3 rounded-2xl transition-all duration-300 transform active:scale-98">
             Déconnexion
          </button>
        </div>
      </header>

      <!-- Products Grid Component -->
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

    <!-- Cart Sidebar Component -->
    <CartSidebar 
      :clients="clients"
      @openClientModal="showClientModal = true"
      @orderSubmitted="handleOrderSubmitted"
    />

    <!-- QUICK ADD CLIENT MODAL: Beautiful frosted-glass with springy scale-up animations -->
    <div v-if="showClientModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-[100] flex items-center justify-center p-4">
        <div class="bg-white/95 backdrop-blur-xl rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-2xl scale-in-center border border-slate-100/50">
            <div class="p-8 border-b border-slate-200/60 bg-white/50 flex justify-between items-center">
                <div>
                  <h3 class="font-black text-2xl text-slate-900 tracking-tight">Nouveau Client</h3>
                  <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Ajout rapide</p>
                </div>
                <button @click="showClientModal = false" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-2xl text-slate-400 hover:text-slate-900 hover:bg-slate-200 transition-all duration-300">
                    <XIcon class="w-5 h-5" />
                  </button>
            </div>
            <div class="p-8 space-y-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nom complet *</label>
                    <input type="text" v-model="newClient.name" class="w-full p-4 bg-slate-50 border border-slate-200 focus:bg-white rounded-2xl focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 font-bold transition-all duration-300" placeholder="Ex: Hassan Najjar">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Téléphone</label>
                    <input type="text" v-model="newClient.phone" class="w-full p-4 bg-slate-50 border border-slate-200 focus:bg-white rounded-2xl focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 font-bold transition-all duration-300" placeholder="06...">
                </div>
            </div>
            <div class="p-8 bg-slate-50/50 flex justify-end space-x-3 border-t border-slate-100">
                <button @click="showClientModal = false" class="px-6 py-3 font-black text-slate-500 hover:text-slate-800 transition-colors duration-300 uppercase text-[10px] tracking-widest">Annuler</button>
                <button @click="saveNewClient" :disabled="!newClient.name || isSubmittingClient" class="px-8 py-3.5 font-black text-white bg-brand-600 hover:bg-brand-700 rounded-2xl transition-all duration-300 shadow-lg shadow-brand-200 disabled:opacity-50 uppercase text-[10px] tracking-widest flex items-center transform active:scale-98">
                    <Loader2Icon v-if="isSubmittingClient" class="w-4 h-4 mr-2 animate-spin" />
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
import { LayoutDashboardIcon, SearchIcon, XIcon, Loader2Icon } from 'lucide-vue-next';

// Components
import ProductGrid from '@/Components/Pos/ProductGrid.vue';
import CartSidebar from '@/Components/Pos/CartSidebar.vue';

const cartStore = useCartStore();
const toast = useToast();
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const companyName = computed(() => (window.appSettings || {}).company_name || 'Mon Entreprise');
const logout = () => router.post('/logout');

const props = defineProps({
  initialClients: Array,
  initialServices: Array,
  initialPanels: Array,
  initialCantos: Array,
  initialConsumables: Array
});

// Global Data State (Reactive copies of props)
const clients = computed(() => props.initialClients);
const services = computed(() => props.initialServices);
const panels = computed(() => props.initialPanels);
const cantos = computed(() => props.initialCantos);
const consumables = computed(() => props.initialConsumables);
const isLoading = ref(false);

// Search & Filtering
const searchQuery = ref('');
const selectedCategory = ref('all');

// Sync store data for derived logic (like poseService)
onMounted(() => {
    cartStore.services = props.initialServices;
    cartStore.panels = props.initialPanels;
    cartStore.cantos = props.initialCantos;
});

// Client Modal State
const showClientModal = ref(false);
const isSubmittingClient = ref(false);
const newClient = ref({ name: '', phone: '' });

const saveNewClient = async () => {
    if (!newClient.value.name) return;
    isSubmittingClient.value = true;
    try {
        const res = await axios.post('/api/clients', newClient.value);
        // We can just use router.reload() to refresh the clients prop
        router.reload({ 
            only: ['initialClients'],
            onSuccess: () => {
                cartStore.selectedClient = res.data.id;
                showClientModal.value = false;
                newClient.value = { name: '', phone: '' };
            }
        });
    } catch (error) {
        console.error('Erreur ajout client:', error);
        toast.error('Erreur lors de l\'ajout du client.');
    } finally {
        isSubmittingClient.value = false;
    }
};

const handleOrderSubmitted = (data) => {
    // No manual fetching needed! Inertia handles it via the router.post success.
};
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 12px; }

.scale-in-center {
	animation: scale-in-center 0.4s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
}
@keyframes scale-in-center {
  0% { transform: scale(0.92); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
