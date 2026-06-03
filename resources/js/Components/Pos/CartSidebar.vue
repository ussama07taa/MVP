<template>
  <!-- RIGHT PANEL: Cart -->
  <div class="w-full bg-[#fafbfc] flex flex-col h-full border-l border-slate-200/80 relative print:hidden">

    <!-- Client Selector -->
    <div class="shrink-0 px-4 py-3 border-b border-slate-200/60 bg-white/90 backdrop-blur-md shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Client / Projet</label>
        <button @click="$emit('openClientModal')"
          class="flex items-center gap-1 text-[9px] font-black text-brand-600 hover:text-brand-800 bg-brand-50 border border-brand-100/50 px-2 py-1 rounded-lg uppercase tracking-wider transition-all active:scale-95">
          <PlusCircleIcon class="w-3 h-3" /> Nouveau
        </button>
      </div>
      <div class="relative">
        <UserIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
        <select v-model="cartStore.selectedClient"
          class="w-full pl-9 pr-8 py-2.5 bg-slate-50 border border-slate-200 hover:border-slate-300 focus:bg-white focus:border-brand-500 rounded-xl appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500/10 font-bold text-sm text-slate-700 shadow-sm transition-all cursor-pointer">
          <option value="" disabled selected>Sélectionner un client...</option>
          <option v-for="cl in clients" :value="cl.id" :key="cl.id">{{ cl.name }} — {{ cl.total_credit }} DH</option>
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>
    </div>

    <!-- Cart Items -->
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2.5 custom-scrollbar">

      <!-- Empty state -->
      <div v-if="cartStore.cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 py-10">
        <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mb-4 floating-icon">
          <ShoppingCartIcon class="w-9 h-9 text-slate-300" />
        </div>
        <p class="font-black text-slate-700 text-base">Panier vide</p>
        <p class="text-[11px] font-bold text-slate-400 mt-1 text-center px-6 leading-relaxed">Ajoutez des articles depuis la liste de gauche</p>
      </div>

      <!-- Items -->
      <TransitionGroup name="list" tag="div" class="space-y-2.5">
        <div v-for="(item, index) in cartStore.cart" :key="item.type + item.id"
          class="bg-white rounded-2xl border border-slate-200/60 shadow-[0_2px_12px_rgba(15,23,42,0.04)] hover:border-brand-200/40 transition-all duration-200 overflow-hidden">

          <!-- Item header -->
          <div class="flex items-start justify-between p-3.5">
            <div class="flex-1 pr-2 min-w-0">
              <p class="font-black text-slate-800 text-[11px] uppercase tracking-tight leading-snug">{{ formatItemLabel(item.name) }}</p>
              <div v-if="item.width_mm && item.thickness_mm" class="mt-0.5">
                <span class="text-[8px] font-black bg-slate-50 text-emerald-600 px-1.5 py-0.5 rounded border border-slate-100 uppercase tracking-tighter">{{ item.width_mm }}×{{ item.thickness_mm }}mm</span>
              </div>

              <!-- Qty + Price row -->
              <div class="flex items-center gap-2 mt-2.5 flex-wrap">
                <!-- Quantity -->
                <div class="flex items-center bg-slate-50 rounded-lg border border-slate-200 px-2 py-1">
                  <input type="number"
                    :value="item.quantity"
                    @change="cartStore.handleQuantityChange(item, $event.target.value)"
                    min="0.1" step="0.1"
                    class="w-10 h-6 text-xs font-black text-center bg-transparent border-none focus:ring-0 p-0 text-slate-700">
                  <span class="text-[9px] font-black text-slate-400 ml-1 uppercase">
                    {{ item.type === 'canto' ? 'm' : (item.type === 'panel' ? 'pcs' : (item.type === 'custom_labor' && item.unit === 'mètre' ? 'm' : 'u')) }}
                  </span>
                </div>
                <span class="text-slate-300 font-black text-xs">×</span>
                <!-- Price -->
                <div v-if="item.type === 'service' || item.type === 'custom_labor'"
                  class="flex items-center bg-white rounded-lg border border-brand-200 px-2 py-1">
                  <input type="number" v-model="item.unit_price"
                    class="w-16 h-6 text-xs font-black text-center bg-transparent border-none focus:ring-0 p-0 text-brand-600">
                  <span class="text-[9px] font-black text-brand-400 ml-0.5">DH</span>
                </div>
                <div v-else class="px-2 py-1 bg-slate-50 rounded-lg border border-slate-100 text-xs font-black text-slate-600">
                  {{ Number(item.unit_price).toFixed(2) }} <span class="text-[9px] text-slate-400">DH</span>
                </div>
              </div>
            </div>

            <div class="flex flex-col items-end shrink-0 gap-2">
              <span class="font-black text-slate-900 text-sm">{{ (item.quantity * item.unit_price).toFixed(2) }} <span class="text-[9px] text-slate-400 font-bold">DH</span></span>
              <button @click="cartStore.removeFromCart(index)"
                class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all">
                <Trash2Icon class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Canto services toggle -->
          <div v-if="item.type === 'canto'" class="mx-3.5 mb-3 p-3 bg-slate-50 border border-slate-100 rounded-xl">
            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-2">Façonnage</span>
            <label class="flex items-center justify-between cursor-pointer">
              <div class="flex items-center gap-2">
                <input type="checkbox" v-model="item.with_canto_service" @change="cartStore.updateCantoPrices(item)"
                  class="w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500">
                <span class="text-[11px] font-black text-slate-700">Collage Chant</span>
              </div>
            </label>
            <div v-if="item.with_canto_service" class="mt-2 flex items-center justify-between bg-white border border-slate-100 px-3 py-2 rounded-lg">
              <span class="text-[9px] font-bold text-slate-500">Tarif (DH/m) :</span>
              <input type="number" v-model.number="item.custom_canto_service_price"
                @input="cartStore.updateCantoPrices(item)" min="0" step="0.5"
                class="w-14 text-right py-0.5 px-1 text-[11px] font-black text-emerald-600 bg-slate-50 border border-slate-200 rounded focus:ring-emerald-500 focus:bg-white transition-all">
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Checkout Footer -->
    <div class="shrink-0 bg-slate-950 text-white px-4 pt-4 pb-5 rounded-t-2xl shadow-2xl border-t border-slate-900">
      <!-- Total -->
      <div class="flex justify-between items-baseline mb-4">
        <span class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Total à payer</span>
        <div>
          <span class="text-3xl font-black tracking-tight text-emerald-400">{{ cartStore.cartTotal.toFixed(2) }}</span>
          <span class="text-base font-bold text-slate-500 ml-1">DH</span>
        </div>
      </div>

      <!-- Amount paid -->
      <div class="mb-3 bg-slate-900 p-3 rounded-xl border border-slate-800">
        <div class="flex items-center justify-between mb-2">
          <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Avance (Espèces)</label>
          <span v-if="cartStore.remainingCredit > 0" class="text-rose-400 text-[9px] font-black bg-rose-400/10 px-2 py-0.5 rounded-md">
            Reste: {{ cartStore.remainingCredit.toFixed(2) }} DH
          </span>
        </div>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-black text-sm">DH</span>
          <input type="number" v-model="cartStore.amountPaid"
            class="w-full pl-9 pr-3 py-2.5 bg-black border border-slate-800 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 font-black text-xl text-white placeholder-slate-800 transition-all text-center tracking-wide"
            placeholder="0.00">
        </div>
      </div>

      <!-- Workshop toggle -->
      <div class="mb-3 bg-slate-900 px-3 py-2.5 rounded-xl border border-slate-800">
        <label class="flex items-center justify-between cursor-pointer select-none">
          <div class="flex items-center gap-2">
            <HammerIcon class="w-3.5 h-3.5 text-brand-400" />
            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Envoyer à l'Atelier</span>
          </div>
          <input type="checkbox" v-model="sendToWorkshop" class="w-4.5 h-4.5 text-brand-500 border-slate-600 bg-slate-800 rounded focus:ring-brand-500 cursor-pointer">
        </label>
        <Transition name="slide-down">
          <input v-if="sendToWorkshop" type="text" v-model="workshopNotes"
            class="w-full mt-2 px-3 py-2 bg-black border border-slate-800 rounded-lg text-xs font-bold text-white placeholder-slate-700 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
            placeholder="Notes pour l'atelier (optionnel)...">
        </Transition>
      </div>

      <!-- Submit -->
      <button @click="submitOrder"
        :disabled="cartStore.cart.length === 0 || !cartStore.selectedClient || isProcessing"
        class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-black py-3.5 rounded-xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:translate-y-0 flex justify-center items-center gap-2 text-sm tracking-wide active:scale-[0.99]">
        <template v-if="isProcessing">
          <Loader2Icon class="w-5 h-5 animate-spin" /> TRAITEMENT...
        </template>
        <template v-else>
          <CheckCircleIcon class="w-5 h-5" /> VALIDER LA COMMANDE
        </template>
      </button>
    </div>

    <!-- Print template -->
    <InvoiceTemplate
      v-if="lastOrder"
      :order="lastOrder"
      :items="lastOrder.items"
      :total="lastOrder.total"
      :amountPaid="lastOrder.amount_paid"
      :clientName="lastOrder.client_name"
    />
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useCartStore } from '@/stores/cart';
import { usePrint } from '@/composables/usePrint';
import { useToast } from '@/composables/useToast';
import InvoiceTemplate from '@/Components/Print/InvoiceTemplate.vue';
import { PlusCircleIcon, UserIcon, ShoppingCartIcon, Trash2Icon, CheckCircleIcon, Loader2Icon, HammerIcon } from 'lucide-vue-next';

const props = defineProps({ clients: Array });
const emit = defineEmits(['openClientModal', 'orderSubmitted']);

const cartStore = useCartStore();
const { printOrder } = usePrint();
const toast = useToast();
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
    toast.error('Le montant payé ne peut pas être supérieur au total de la facture.');
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
      base_canto_price: i.base_canto_price || 0,
      base_name: i.base_name,
      width_mm: i.width_mm,
      thickness_mm: i.thickness_mm,
    }))
  };

  router.post('/api/admin/orders/checkout', payload, {
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
        toast.success('Facture validée avec succès !');
        cartStore.clearCart();
        workshopNotes.value = '';
        emit('orderSubmitted');
      });
    },
    onError: (errors) => toast.error('Erreur: ' + Object.values(errors).join(', ')),
    onFinish: () => { isProcessing.value = false; }
  });
};
</script>

<style scoped>
.floating-icon { animation: float 4s ease-in-out infinite; }
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-8px); }
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }

.list-enter-active, .list-leave-active { transition: all 0.35s cubic-bezier(0.165,0.84,0.44,1); }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateY(10px) scale(0.97); }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
