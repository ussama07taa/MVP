<template>
  <!-- RIGHT PANEL: Cart & Checkout (Elite Receipt Style) -->
  <div class="w-full lg:w-[35%] xl:w-[30%] bg-[#fafbfc] shadow-[-20px_0_50px_rgba(15,23,42,0.04)] flex flex-col h-full border-l border-slate-200/80 z-30 relative print:hidden">
    
    <!-- Client Selector with Frosted Glass styling -->
    <div class="p-6 border-b border-slate-200/60 bg-white/90 backdrop-blur-md relative z-20 shadow-[0_2px_15px_rgba(0,0,0,0.015)]">
      <div class="flex items-center justify-between mb-2.5">
        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Client / Projet</label>
        <button @click="$emit('openClientModal')" class="text-[10px] font-black text-brand-600 hover:text-brand-800 bg-brand-50 border border-brand-100/50 px-2.5 py-1.5 rounded-lg uppercase tracking-wider transition-all duration-300 transform active:scale-95">
          <PlusCircleIcon class="w-3.5 h-3.5 inline mr-1" /> Nouveau
        </button>
      </div>
      <div class="relative group">
        <UserIcon class="w-5 h-5 absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-hover:text-brand-500 transition-colors duration-300" />
        <select v-model="cartStore.selectedClient" class="w-full pl-12 pr-10 py-3.5 bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-brand-500 rounded-2xl appearance-none focus:outline-none focus:ring-4 focus:ring-brand-500/10 font-bold text-slate-700 shadow-sm transition-all duration-300 cursor-pointer">
          <option value="" disabled selected>Sélectionner un client...</option>
          <option v-for="cl in clients" :value="cl.id" :key="cl.id">{{ cl.name }} (Crédit: {{ cl.total_credit }} DH)</option>
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400 group-hover:text-brand-500 transition-colors duration-300">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
      </div>
    </div>

    <!-- Cart Items List (Scrollable with premium list-item animations) -->
    <div class="flex-grow overflow-y-auto p-6 bg-slate-50/20 custom-scrollbar relative">
      <div v-if="cartStore.cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 animate-fade-in-slow">
        <!-- Floating Shop Bag Illustration with interactive float animation -->
        <div class="w-28 h-28 bg-gradient-to-br from-slate-100 to-slate-200/50 rounded-full flex items-center justify-center mb-6 shadow-inner border border-slate-200/20 relative group-hover:scale-105 transition-transform duration-500 floating-icon">
          <ShoppingCartIcon class="w-11 h-11 text-slate-400" />
          <div class="absolute -top-1 -right-1 w-6 h-6 bg-brand-500 text-white text-[10px] font-black rounded-full flex items-center justify-center shadow-lg shadow-brand-500/25">0</div>
        </div>
        <p class="text-xl font-black text-slate-800 tracking-tight">Le panier est vide</p>
        <p class="text-xs font-bold mt-2 text-slate-400 text-center px-8 uppercase tracking-wider leading-relaxed">Sélectionnez des articles à gauche pour commencer la facturation.</p>
      </div>

      <TransitionGroup name="list" tag="div" class="space-y-4">
        <div v-for="(item, index) in cartStore.cart" :key="item.type + item.id" class="flex flex-col p-5 bg-white rounded-3xl shadow-[0_4px_20px_rgba(15,23,42,0.015)] hover:shadow-[0_8px_30px_rgba(15,23,42,0.03)] border border-slate-200/50 hover:border-brand-200/40 transition-all duration-300 group relative overflow-hidden">
          <div class="flex justify-between items-start">
            <div class="flex-1 pr-4">
              <!-- Professional Label Formatting -->
              <p class="font-black text-slate-800 text-sm leading-snug uppercase tracking-tight">{{ formatItemLabel(item.name) }}</p>
              
              <div class="flex flex-wrap items-center mt-4 gap-2">
                <!-- Quantity selector -->
                <div class="flex items-center bg-slate-50 rounded-xl border border-slate-200/80 px-2 py-1 shadow-sm">
                  <input type="number" 
                    :value="item.quantity" 
                    @change="cartStore.handleQuantityChange(item, $event.target.value)"
                    min="0.1" step="0.1" 
                    class="w-11 h-7 text-xs font-black text-center bg-transparent border-none focus:ring-0 p-0 text-slate-700 font-black" 
                    placeholder="Qté">
                  <span class="text-[9px] font-black text-slate-400 ml-1 uppercase tracking-widest select-none">
                    {{ item.type === 'canto' ? 'm' : (item.type === 'panel' ? 'pcs' : (item.type === 'custom_labor' && item.unit === 'mètre' ? 'm' : 'pcs')) }}
                  </span>
                </div>
                <span class="text-slate-300 font-black text-xs">×</span>
                
                <!-- Price selector / Display -->
                <div v-if="item.type === 'service' || item.type === 'custom_labor'" class="flex items-center bg-white rounded-xl border border-brand-200 px-2 py-0.5 shadow-sm">
                  <input type="number" 
                    v-model="item.unit_price" 
                    class="w-20 h-7 text-xs font-black text-center bg-transparent border-none focus:ring-0 p-0 text-brand-600 focus:bg-slate-50 rounded" 
                    placeholder="Prix">
                  <span class="text-[9px] font-black text-brand-400 ml-1 select-none">DH</span>
                </div>
                <div v-else class="px-3 py-1.5 bg-slate-100/50 rounded-xl border border-slate-200/30 text-xs font-black text-slate-600">
                  {{ Number(item.unit_price).toFixed(2) }} <span class="text-[9px] text-slate-400">DH</span>
                </div>
              </div>
            </div>
            
            <div class="flex flex-col items-end shrink-0">
              <span class="font-black text-slate-900 text-base mb-3">{{ (item.quantity * item.unit_price).toFixed(2) }} <span class="text-[10px] text-slate-400 font-bold uppercase">DH</span></span>
              <button @click="cartStore.removeFromCart(index)" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all duration-300 transform hover:scale-105 active:scale-95">
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>
          </div>
          
          <!-- Services Toggles for Canto (Collage) -->
          <div v-if="item.type === 'canto'" class="mt-4 flex flex-col bg-slate-50 border border-slate-200/60 p-4 rounded-2xl transition-all duration-300">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Services de Façonnage</span>
            
            <!-- Option: Collage de Chant -->
            <div class="flex flex-col">
              <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer select-none">
                  <input type="checkbox" v-model="item.with_canto_service" @change="cartStore.updateCantoPrices(item)" class="w-4.5 h-4.5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 mr-2.5 transition-all">
                  <span class="text-xs font-black text-slate-700 uppercase tracking-wide">Collage Chant</span>
                </label>
              </div>
              <div v-if="item.with_canto_service" class="mt-3 flex items-center justify-between bg-white border border-slate-100 p-2.5 rounded-xl shadow-sm">
                 <span class="text-[9px] font-bold text-slate-500 uppercase">Tarif Collage (DH/m) :</span>
                 <input type="number" v-model.number="item.custom_canto_service_price" @input="cartStore.updateCantoPrices(item)" min="0" step="0.5" class="w-16 text-right py-1 px-2 text-xs font-black text-emerald-600 bg-slate-50 border border-slate-200 rounded-lg focus:ring-emerald-500 focus:bg-white transition-all">
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Checkout Footer (Cash Register Dashboard Style) -->
    <div class="p-6 bg-slate-950 text-white rounded-t-[2rem] shadow-[0_-20px_50px_rgba(15,23,42,0.25)] relative z-40 border-t border-slate-900">
      <div class="flex justify-between items-end mb-6">
        <span class="text-slate-400 font-bold uppercase tracking-widest text-xs">Total à payer</span>
        <div class="text-right">
          <span class="text-4xl font-black tracking-tight text-emerald-400">{{ cartStore.cartTotal.toFixed(2) }}</span>
          <span class="text-xl font-bold text-slate-500 ml-1">DH</span>
        </div>
      </div>
      
      <div class="mb-6 bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-inner">
        <label class="flex justify-between items-center text-xs font-bold text-slate-300 uppercase tracking-widest mb-3">
          <span>Avance reçue (Espèces)</span>
          <span v-if="cartStore.remainingCredit > 0" class="text-rose-400 bg-rose-400/10 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider">Reste: {{ cartStore.remainingCredit.toFixed(2) }} DH</span>
        </label>
        <div class="relative">
          <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-600 font-black">DH</span>
          <input type="number" v-model="cartStore.amountPaid" class="w-full pl-12 pr-4 py-3.5 bg-black border border-slate-800 rounded-xl focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-black text-2xl text-white shadow-inner placeholder-slate-800 transition-all text-center tracking-wide" placeholder="0.00">
        </div>
      </div>

      <!-- Workshop Toggle -->
      <div class="mb-4 bg-slate-900 p-4 rounded-2xl border border-slate-800">
        <label class="flex items-center justify-between cursor-pointer select-none">
          <div class="flex items-center gap-2">
            <HammerIcon class="w-4 h-4 text-brand-400" />
            <span class="text-xs font-black text-slate-300 uppercase tracking-widest">Envoyer à l'Atelier</span>
          </div>
          <input type="checkbox" v-model="sendToWorkshop" class="w-5 h-5 text-brand-500 border-slate-600 bg-slate-800 rounded focus:ring-brand-500 cursor-pointer">
        </label>
        <Transition name="slide">
          <div v-if="sendToWorkshop" class="mt-3">
            <input type="text" v-model="workshopNotes" class="w-full px-3 py-2.5 bg-black border border-slate-800 rounded-xl text-sm font-bold text-white placeholder-slate-700 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all" placeholder="Notes pour l'atelier (optionnel)...">
          </div>
        </Transition>
      </div>

      <button @click="submitOrder" :disabled="cartStore.cart.length === 0 || !cartStore.selectedClient || isProcessing" 
        class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:translate-y-0 flex justify-center items-center text-lg tracking-wide relative transform active:scale-[0.99]">
        <template v-if="isProcessing">
           <Loader2Icon class="w-6 h-6 mr-2 animate-spin" />
           TRAITEMENT...
        </template>
        <template v-else>
           <CheckCircleIcon class="w-6 h-6 mr-2" /> 
           VALIDER LA COMMANDE
        </template>
      </button>
    </div>
  </div>

  <!-- HIDDEN PRINT TEMPLATE -->
  <InvoiceTemplate 
    v-if="lastOrder"
    :order="lastOrder"
    :items="lastOrder.items"
    :total="lastOrder.total"
    :amountPaid="lastOrder.amount_paid"
    :clientName="lastOrder.client_name"
  />
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useCartStore } from '@/stores/cart';
import { usePrint } from '@/composables/usePrint';
import InvoiceTemplate from '@/Components/Print/InvoiceTemplate.vue';
import { PlusCircleIcon, UserIcon, ShoppingCartIcon, Trash2Icon, CheckCircleIcon, Loader2Icon, HammerIcon } from 'lucide-vue-next';

const props = defineProps({
  clients: Array
});

const emit = defineEmits(['openClientModal', 'orderSubmitted']);

const cartStore = useCartStore();
const { printOrder } = usePrint();
const isProcessing = ref(false);
const lastOrder = ref(null);
const sendToWorkshop = ref(true);
const workshopNotes = ref('');

const formatItemLabel = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

const submitOrder = async () => {
  if (isProcessing.value) return;
  
  if (Number(cartStore.amountPaid) > cartStore.cartTotal) {
    alert('Erreur: Le montant payé ne peut pas être supérieur au total de la facture.');
    return;
  }

  isProcessing.value = true;
  
  const hasServices = cartStore.cart.some(i => i.type === 'service' || i.type === 'custom_labor' || i.with_canto_service);

  const payload = { 
    client_id: cartStore.selectedClient, 
    amount_paid: cartStore.amountPaid || 0, 
    send_to_workshop: sendToWorkshop.value && hasServices,
    workshop_notes: workshopNotes.value || '',
    items: cartStore.cart.map(i => ({ 
      type: i.type, 
      id: i.id, 
      quantity: i.quantity, 
      unit_price: i.unit_price,
      name: i.name,
      with_pose: i.with_pose || false,
      custom_pose_price: i.custom_pose_price || 0,
      with_canto_service: i.with_canto_service || false,
      custom_canto_service_price: i.custom_canto_service_price || 0,
      base_canto_price: i.base_canto_price || 0
    }))
  };

  router.post('/api/orders/checkout', payload, {
    onSuccess: (page) => {
      lastOrder.value = {
        id: page.props.flash?.order_id || 'TEMP',
        items: [...cartStore.cart],
        total: cartStore.cartTotal,
        amount_paid: cartStore.amountPaid || 0,
        client_name: props.clients.find(c => c.id === cartStore.selectedClient)?.name || 'Client'
      };

      nextTick(() => {
        printOrder();
        alert('Facture validée avec succès !');
        cartStore.clearCart();
        workshopNotes.value = '';
        emit('orderSubmitted');
      });
    },
    onError: (errors) => {
      alert('Erreur lors de la validation: ' + Object.values(errors).join(', '));
    },
    onFinish: () => {
      isProcessing.value = false;
    }
  });
};
</script>

<style scoped>
.floating-icon {
  animation: float 4s ease-in-out infinite;
}

@keyframes float {
  0% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-8px) rotate(2deg); }
  100% { transform: translateY(0px) rotate(0deg); }
}

.animate-fade-in-slow {
  animation: fadeInSlow 0.6s ease-out both;
}

@keyframes fadeInSlow {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}

/* Fluid List Transitions */
.list-enter-active,
.list-leave-active {
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.95);
}
</style>
