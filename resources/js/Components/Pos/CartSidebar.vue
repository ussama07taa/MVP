<template>
  <!-- RIGHT PANEL: Cart -->
  <div class="w-full bg-white flex flex-col h-full border-l border-slate-200/50 relative print:hidden">

    <!-- Premium Client Selector — Compacted -->
    <div class="shrink-0 px-5 py-4 border-b border-slate-100 bg-white/70 backdrop-blur-xl">
      <div class="flex items-center justify-between mb-2">
        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Client</label>
        <button @click="$emit('openClientModal')"
          class="flex items-center gap-1 text-[9px] font-black text-amber-600 hover:text-amber-700 bg-amber-50 px-2 py-1 rounded-xl uppercase tracking-wider transition-all active:scale-95 border border-amber-100">
          <PlusCircleIcon class="w-3 h-3" /> Nouveau
        </button>
      </div>
      <div class="relative group">
        <UserIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-amber-500 transition-colors pointer-events-none" />
        <select v-model="cartStore.selectedClient"
          class="w-full pl-9 pr-8 py-2.5 bg-slate-100/50 border border-transparent hover:bg-slate-100 focus:bg-white focus:border-amber-500/20 rounded-xl appearance-none focus:outline-none focus:ring-4 focus:ring-amber-500/5 font-bold text-[13px] text-slate-900 shadow-inner transition-all cursor-pointer">
          <option value="" disabled selected>Affecter à...</option>
          <option v-for="cl in clients" :value="cl.id" :key="cl.id">{{ cl.name }} ({{ Number(cl.total_credit).toFixed(0) }} DH)</option>
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>
    </div>

    <!-- Cart Items — More Compact -->
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2.5 custom-scrollbar bg-slate-50/30">

      <!-- Empty state -->
      <div v-if="cartStore.cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 py-12">
        <div class="w-16 h-16 rounded-[1.5rem] bg-white shadow-lg shadow-slate-200/50 flex items-center justify-center mb-4">
          <ShoppingCartIcon class="w-7 h-7 text-slate-200" />
        </div>
        <p class="font-bold text-slate-900 text-sm tracking-tight uppercase tracking-widest">Panier vide</p>
      </div>

      <!-- Items -->
      <TransitionGroup name="list" tag="div" class="space-y-2.5">
        <div v-for="(item, index) in cartStore.cart" :key="item.type + item.id"
          class="bg-white rounded-2xl p-3 border border-slate-200/40 shadow-sm hover:shadow-md transition-all duration-300 group">

          <!-- Item main content -->
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5 mb-1">
                 <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                 <p class="font-bold text-slate-900 text-[11px] uppercase tracking-tight leading-tight line-clamp-1">{{ formatItemLabel(item.name) }}</p>
              </div>
              
              <div v-if="item.width_mm && item.thickness_mm" class="mb-2">
                <span class="text-[8px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-lg uppercase tracking-widest">{{ item.width_mm }}×{{ item.thickness_mm }}mm</span>
              </div>

              <!-- Control row -->
              <div class="flex items-center gap-2">
                <div class="flex items-center bg-slate-50 border border-slate-100 rounded-lg p-0.5 shrink-0">
                  <input type="number"
                    :value="item.quantity"
                    @change="cartStore.handleQuantityChange(item, $event.target.value)"
                    min="0.1" step="0.1"
                    class="w-10 h-7 text-[12px] font-bold text-center bg-transparent border-none focus:ring-0 p-0 text-slate-900">
                  <span class="text-[9px] font-bold text-slate-400 pr-2 uppercase">{{ item.type === 'canto' ? 'm' : (item.type === 'panel' ? 'pcs' : 'u') }}</span>
                </div>
                
                <span class="text-slate-300 text-[10px]">at</span>

                <div v-if="item.type === 'service' || item.type === 'custom_labor'"
                  class="flex items-center bg-amber-50/50 border border-amber-100 rounded-lg px-2 py-1 shrink-0">
                  <input type="number" v-model="item.unit_price"
                    class="w-12 h-5 text-[11px] font-bold text-center bg-transparent border-none focus:ring-0 p-0 text-amber-700">
                  <span class="text-[9px] font-black text-amber-500 ml-0.5">DH</span>
                </div>
                <div v-else class="px-2 py-1 bg-slate-50 rounded-lg text-[11px] font-bold text-slate-600 border border-slate-100/50">
                  {{ Number(item.unit_price).toFixed(2) }}
                </div>
              </div>
            </div>

            <!-- Item Total & Actions -->
            <div class="flex flex-col items-end shrink-0 gap-3">
              <span class="font-black text-slate-950 text-sm tracking-tight">{{ (item.quantity * item.unit_price).toFixed(2) }}<span class="text-[9px] text-slate-400 font-bold ml-1">DH</span></span>
              <button @click="cartStore.removeFromCart(index)"
                class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                <Trash2Icon class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Special Canto Service Expand (Inside Loop) -->
          <div v-if="item.type === 'canto'" class="mt-3 pt-3 border-t border-slate-100/50">
            <label class="flex items-center gap-2 cursor-pointer group/toggle">
              <div class="relative w-8 h-4 bg-slate-200 rounded-full transition-colors group-has-[:checked]:bg-amber-500">
                <input type="checkbox" v-model="item.with_canto_service" @change="cartStore.updateCantoPrices(item)" class="sr-only">
                <div class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full transition-transform group-has-[:checked]:translate-x-4"></div>
              </div>
              <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Collage de chant</span>
            </label>
            <div v-if="item.with_canto_service" class="mt-2 flex items-center justify-between bg-slate-50 px-3 py-2 rounded-xl border border-slate-100">
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Tarif (DH/m)</span>
              <input type="number" v-model.number="item.custom_canto_service_price"
                @input="cartStore.updateCantoPrices(item)" min="0" step="0.5"
                class="w-12 text-right bg-transparent border-none text-slate-900 font-black text-[11px] focus:ring-0 p-0">
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Checkout Footer — Harmonized Colors -->
    <div class="shrink-0 bg-slate-900/95 text-white px-6 py-6 rounded-t-[2rem] shadow-[-10px_-10px_40px_rgba(0,0,0,0.1)] border-t border-slate-800/50">
      
      <!-- Total Section -->
      <div class="flex justify-between items-baseline mb-6">
        <div>
          <span class="text-slate-500 font-bold uppercase tracking-[0.2em] text-[9px] block mb-1">Total Net</span>
          <div class="flex items-baseline gap-1.5">
            <span class="text-3xl font-black tracking-tighter text-amber-500">{{ cartStore.cartTotal.toFixed(2) }}</span>
            <span class="text-sm font-bold text-slate-500 uppercase">DH</span>
          </div>
        </div>
        <div class="text-right">
          <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block mb-1">Items</span>
          <span class="text-lg font-black text-white">{{ cartStore.cart.length }}</span>
        </div>
      </div>

      <!-- Payment -->
      <div class="mb-5">
        <div class="relative group">
          <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 font-bold text-sm pointer-events-none group-focus-within:text-amber-500 transition-colors">DH</div>
          <input type="number" v-model="cartStore.amountPaid"
            class="w-full pl-12 pr-6 py-4 bg-slate-950/50 border border-slate-800 rounded-2xl focus:outline-none focus:border-amber-500/50 focus:ring-4 focus:ring-amber-500/5 font-black text-2xl text-white placeholder-slate-800 transition-all text-center tracking-tight"
            placeholder="Avance (Cash)">
        </div>
        <div v-if="cartStore.remainingCredit > 0" class="mt-2 text-center">
          <span class="text-rose-400 text-[9px] font-bold uppercase tracking-[0.1em]">Reste à crédit: {{ cartStore.remainingCredit.toFixed(2) }} DH</span>
        </div>
      </div>

      <!-- Compact Workshop Button -->
      <button @click="sendToWorkshop = !sendToWorkshop" 
        class="w-full mb-3 flex items-center justify-between px-5 py-3 rounded-xl border transition-all"
        :class="sendToWorkshop ? 'bg-amber-600 border-transparent text-white shadow-lg' : 'bg-slate-800/40 border-slate-800 text-slate-500 hover:text-slate-300'">
        <div class="flex items-center gap-2">
          <HammerIcon class="w-4 h-4" />
          <span class="text-[10px] font-bold uppercase tracking-wider">Atelier</span>
        </div>
        <div class="w-1.5 h-1.5 rounded-full" :class="sendToWorkshop ? 'bg-white animate-pulse' : 'bg-slate-700'"></div>
      </button>

      <!-- SketchCut PDF Upload (Conditional) -->
      <div v-if="sendToWorkshop" class="mb-5">
        <input type="file" @change="handleTefsilUpload" class="hidden" ref="tefsilInput" accept=".pdf,.jpg,.jpeg,.png">
        <button @click="$refs.tefsilInput.click()" 
          class="w-full h-11 flex items-center justify-center gap-2 border-2 border-dashed border-slate-700/50 rounded-xl hover:border-amber-500/50 hover:bg-amber-500/5 transition-all group/btn">
          <FileTextIcon class="w-3.5 h-3.5" :class="tefsilFile ? 'text-amber-500' : 'text-slate-500'" />
          <span class="text-[9px] font-black uppercase tracking-wider truncate px-2" :class="tefsilFile ? 'text-amber-500' : 'text-slate-400'">
            {{ tefsilFile ? tefsilFile.name : 'Attacher Plan SketchCut' }}
          </span>
        </button>
      </div>

      <!-- Submit -->
      <button @click="submitOrder"
        :disabled="cartStore.cart.length === 0 || !cartStore.selectedClient || isProcessing"
        class="w-full bg-amber-500 text-slate-950 font-black py-4 rounded-2xl shadow-lg shadow-amber-500/10 hover:shadow-amber-500/20 active:scale-[0.98] transition-all duration-300 disabled:opacity-20 disabled:grayscale text-[13px] uppercase tracking-widest flex justify-center items-center gap-2">
        <template v-if="isProcessing">
          <Loader2Icon class="w-5 h-5 animate-spin" />
        </template>
        <template v-else>
          <CheckCircleIcon class="w-5 h-5" /> VALIDER
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
import axios from 'axios';
import { useCartStore } from '@/stores/cart';
import { usePrint } from '@/composables/usePrint';
import { useToast } from '@/composables/useToast';
import InvoiceTemplate from '@/Components/Print/InvoiceTemplate.vue';
import { PlusCircleIcon, UserIcon, ShoppingCartIcon, Trash2Icon, CheckCircleIcon, Loader2Icon, HammerIcon, FileTextIcon } from 'lucide-vue-next';

const props = defineProps({ clients: Array });
const emit = defineEmits(['openClientModal', 'orderSubmitted']);

const cartStore = useCartStore();
const { printOrder } = usePrint();
const toast = useToast();
const isProcessing = ref(false);
const lastOrder = ref(null);
const sendToWorkshop = ref(true);
const workshopNotes = ref('');
const tefsilFile = ref(null);

const handleTefsilUpload = (e) => {
  const file = e.target.files[0];
  if (file) tefsilFile.value = file;
};

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

  const formData = new FormData();
  formData.append('client_id', cartStore.selectedClient);
  formData.append('amount_paid', cartStore.amountPaid || 0);
  formData.append('send_to_workshop', sendToWorkshop.value && hasServices ? '1' : '0');
  formData.append('workshop_notes', workshopNotes.value || '');
  formData.append('items', JSON.stringify(cartStore.cart.map(i => ({
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
  }))));
  if (tefsilFile.value) {
    formData.append('tefsil_file', tefsilFile.value);
  }

  try {
    const res = await axios.post('/api/admin/orders/checkout', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    lastOrder.value = {
      id: res.data?.order_id || 'TEMP',
      items: [...cartStore.cart],
      total: cartStore.cartTotal,
      amount_paid: cartStore.amountPaid || 0,
      client_name: props.clients.find(c => c.id === cartStore.selectedClient)?.name || 'Client',
    };

    await nextTick();
    printOrder();
    toast.success('Facture validée avec succès !');
    cartStore.clearCart();
    workshopNotes.value = '';
    tefsilFile.value = null;
    emit('orderSubmitted');
  } catch (error) {
    const msg = error.response?.data?.error
      || error.response?.data?.message
      || Object.values(error.response?.data?.errors || {}).flat().join(', ')
      || error.message
      || 'Erreur lors de la validation.';
    toast.error(msg);
  } finally {
    isProcessing.value = false;
  }
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
