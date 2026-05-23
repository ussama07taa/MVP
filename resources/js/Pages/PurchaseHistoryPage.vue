<template>
  <div class="max-w-7xl mx-auto pb-10">
    <header class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center">
          <HistoryIcon class="w-8 h-8 mr-3 text-brand-600" /> Historique des Achats
        </h1>
        <p class="text-slate-500 font-medium mt-1">Consultez toutes les factures fournisseurs et les entrées de stock rapides.</p>
      </div>
      <div class="flex gap-3">
        <!-- Actualiser Button (Master UX) -->
        <button @click="loadPurchases" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-3.5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-brand-600 transition-transform duration-500" />
        </button>
      </div>
    </header>
    
    <!-- FILTER TOOLBAR -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-[32px] border border-slate-100 mb-6 shadow-soft gap-4">
        <!-- Date Filters -->
        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="flex items-center gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Du</label>
                <div class="relative">
                  <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <input type="date" v-model="startDate" class="pl-10 pr-4 py-2 text-xs font-bold border border-slate-200 rounded-xl focus:outline-none focus:border-brand-500 bg-slate-50/50">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Au</label>
                <div class="relative">
                  <CalendarDaysIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <input type="date" v-model="endDate" class="pl-10 pr-4 py-2 text-xs font-bold border border-slate-200 rounded-xl focus:outline-none focus:border-brand-500 bg-slate-50/50">
                </div>
            </div>
            <button @click="startDate=''; endDate=''" v-if="startDate || endDate" class="p-2 text-slate-400 hover:text-rose-500 transition-colors" title="Effacer les filtres">
                <XCircleIcon class="w-5 h-5" />
            </button>
        </div>

        <!-- View Toggle -->
        <div class="flex bg-slate-100 p-1 rounded-2xl border border-slate-200 shrink-0">
            <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white shadow-sm text-brand-600' : 'text-slate-500 hover:text-slate-700'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center">
                <ListIcon class="w-3.5 h-3.5 mr-2" /> Liste
            </button>
            <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white shadow-sm text-brand-600' : 'text-slate-500 hover:text-slate-700'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center">
                <LayoutGridIcon class="w-3.5 h-3.5 mr-2" /> Grille
            </button>
        </div>
    </div>

    <!-- Error State -->
    <div v-if="errorMessage" class="mb-8 p-6 rounded-[32px] bg-rose-50 border border-rose-100 flex items-center gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
      <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center shrink-0">
        <AlertCircleIcon class="w-6 h-6" />
      </div>
      <div>
        <h4 class="font-black text-rose-900 uppercase text-xs tracking-widest mb-1">Erreur de chargement</h4>
        <p class="text-rose-700 font-bold text-sm">{{ errorMessage }}</p>
      </div>
    </div>

    <!-- LIST VIEW (Unified Design like Sales) -->
    <div v-if="viewMode === 'list'" class="bg-white rounded-[40px] shadow-soft border border-slate-100 overflow-hidden relative p-4">
      <div v-if="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-10 flex items-center justify-center">
         <div class="flex flex-col items-center">
            <div class="w-12 h-12 border-4 border-brand-200 border-t-brand-600 rounded-full animate-spin mb-4"></div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Chargement des données...</span>
         </div>
      </div>

      <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
        <table class="min-w-full border-separate border-spacing-y-3">
          <thead>
            <tr>
              <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Réf</th>
              <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Fournisseur</th>
              <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
              <th class="px-6 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Montant Net</th>
              <th class="px-6 py-3 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
              <th class="px-6 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="purchase in filteredPurchases" :key="purchase.id" class="bg-white border border-slate-100 hover:shadow-lg transition-all duration-300 group">
                <td class="px-6 py-5 rounded-l-[24px]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <FileTextIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="font-black text-slate-900 tracking-tight">{{ purchase.ref }}</div>
                            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ purchase.reference_invoice || 'SANS RÉF' }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-5">
                    <div class="font-black text-slate-700">{{ purchase.supplier_name }}</div>
                </td>
                <td class="px-6 py-5">
                    <div class="flex items-center text-slate-500">
                        <CalendarIcon class="w-3.5 h-3.5 mr-2 opacity-40" />
                        <span class="text-xs font-bold">{{ formatDate(purchase.created_at) }}</span>
                    </div>
                </td>
                <td class="px-6 py-5 text-right">
                    <div class="flex flex-col items-end">
                        <span class="text-sm font-black text-slate-900">{{ safeNumber(purchase.net_amount) }} <span class="text-[10px] text-slate-400">DH</span></span>
                        <span v-if="purchase.total_refund > 0" class="text-[9px] font-bold text-rose-500 uppercase tracking-tighter">(-{{ safeNumber(purchase.total_refund) }} Avoir)</span>
                    </div>
                </td>
                <td class="px-6 py-5 text-center">
                    <span :class="purchase.status_color" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider border shadow-sm">
                        {{ purchase.status }}
                    </span>
                </td>
                <td class="px-6 py-5 text-right rounded-r-[24px]">
                    <div class="flex justify-end gap-2">
                        <button @click="openInvoiceModal(purchase)" class="w-10 h-10 bg-slate-50 text-slate-400 hover:bg-brand-600 hover:text-white rounded-xl flex items-center justify-center transition-all shadow-sm active:scale-90">
                            <EyeIcon class="w-4 h-4" />
                        </button>
                        <a v-if="purchase.document_path" :href="'/storage/' + purchase.document_path" target="_blank" class="w-10 h-10 bg-brand-50 text-brand-600 hover:bg-brand-600 hover:text-white rounded-xl flex items-center justify-center transition-all shadow-sm active:scale-90">
                            <FileDownIcon class="w-4 h-4" />
                        </a>
                    </div>
                </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- GRID VIEW (Cards) -->
    <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div v-for="purchase in filteredPurchases" :key="purchase.id" class="bg-white rounded-[32px] border border-slate-100 shadow-soft hover:shadow-lg transition-all p-6 flex flex-col group relative overflow-hidden">
            <!-- Background Decoration -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full -mr-16 -mt-16 group-hover:bg-brand-50 transition-colors"></div>
            
            <div class="flex justify-between items-start mb-6 relative z-10">
                <div class="flex flex-col">
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 font-black rounded-lg text-[9px] border border-slate-200 uppercase tracking-widest w-fit mb-2">
                        {{ purchase.supplier_name }}
                    </span>
                    <div class="text-sm font-black text-slate-900 tracking-tight">{{ purchase.ref }}</div>
                </div>
                <div :class="purchase.status_color" class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wider border shadow-sm">
                    {{ purchase.status }}
                </div>
            </div>
            
            <div class="flex-1 relative z-10">
                <h3 class="font-black text-slate-800 text-base leading-tight mb-2 group-hover:text-brand-600 transition-colors">{{ purchase.item_name }}</h3>
                <div class="flex items-center text-slate-400 mb-6">
                  <CalendarIcon class="w-3.5 h-3.5 mr-2" />
                  <span class="text-xs font-bold">{{ formatDate(purchase.created_at) }}</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 bg-slate-50 p-4 rounded-2xl border border-slate-100 mb-6">
                    <div class="text-center">
                        <p class="text-[8px] text-slate-400 font-black uppercase tracking-tighter mb-1">Qté</p>
                        <p v-if="purchase.item_price !== null" class="font-black text-slate-800 text-sm">{{ purchase.original_qty }}</p>
                        <p v-else class="font-black text-slate-400 text-sm">-</p>
                    </div>
                    <div class="text-center border-x border-slate-200">
                        <p class="text-[8px] text-slate-400 font-black uppercase tracking-tighter mb-1">P.U</p>
                        <p class="font-black text-slate-800 text-sm">{{ purchase.item_price !== null ? safeNumber(purchase.item_price) : '-' }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[8px] text-slate-400 font-black uppercase tracking-tighter mb-1">Total</p>
                        <p class="font-black text-brand-600 text-sm">{{ safeNumber(purchase.total_amount) }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-auto pt-5 border-t border-slate-50 flex justify-between items-center relative z-10">
                <div class="flex flex-col">
                  <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Règlement</span>
                  <span class="text-xs font-black text-slate-700">{{ safeNumber(purchase.amount_paid) }} DH</span>
                </div>
                <div class="flex gap-2">
                    <button @click="openInvoiceModal(purchase)" class="w-9 h-9 flex items-center justify-center bg-slate-50 text-slate-400 hover:bg-brand-600 hover:text-white rounded-xl transition-all shadow-sm active:scale-90">
                        <EyeIcon class="w-4 h-4" />
                    </button>
                    <a v-if="purchase.document_path" :href="'/storage/' + purchase.document_path" target="_blank" class="w-9 h-9 flex items-center justify-center bg-brand-50 text-brand-600 hover:bg-brand-100 rounded-xl transition-all shadow-sm active:scale-90">
                        <FileDownIcon class="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- EMPTY STATE -->
    <div v-if="filteredPurchases.length === 0 && !isLoading" class="text-center py-24 bg-white rounded-[40px] border-2 border-dashed border-slate-100 animate-in fade-in duration-700">
        <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mx-auto mb-6">
          <TruckIcon class="w-10 h-10" />
        </div>
        <h3 class="text-lg font-black text-slate-800 mb-2">Aucun achat trouvé</h3>
        <p class="text-slate-400 font-bold max-w-xs mx-auto text-sm">Modifiez vos filtres ou la période sélectionnée pour voir d'autres résultats.</p>
        <button @click="startDate=''; endDate=''" class="mt-6 px-6 py-2 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all">
          Réinitialiser les filtres
        </button>
    </div>

    <!-- INVOICE/RECEIPT DETAILS MODAL -->
    <div v-if="showInvoiceModal && activeInvoice" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-2xl mx-4 sm:mx-auto max-h-[90vh] overflow-hidden flex flex-col shadow-2xl animate-in zoom-in-95 duration-200 border border-slate-100">
            <!-- HEADER -->
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-white">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-[20px] flex items-center justify-center">
                        <ReceiptIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Détails de l'Achat</h3>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ activeInvoice.ref }} • {{ formatDate(activeInvoice.created_at) }}</p>
                    </div>
                </div>
                <button @click="closeInvoiceModal" class="w-12 h-12 flex items-center justify-center bg-slate-100 rounded-2xl text-slate-400 hover:text-slate-900 transition-all">
                    <XIcon class="w-6 h-6" />
                </button>
            </div>

            <!-- BODY -->
            <div class="p-8 overflow-y-auto flex-1 custom-scrollbar bg-slate-50/30">
                <!-- Supplier Info -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm mb-8 flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fournisseur</p>
                        <p class="font-black text-slate-800 text-xl tracking-tight">{{ activeInvoice.supplier_name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Statut Règlement</p>
                        <span :class="activeInvoice.status_color" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border shadow-sm">
                            {{ activeInvoice.status }}
                        </span>
                    </div>
                </div>

                <!-- Article Table -->
                <div class="mb-8">
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-2">Articles de la commande</h4>
                    <div class="bg-white rounded-[32px] border border-slate-100 overflow-hidden shadow-sm">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                <tr>
                                    <th class="px-6 py-4">Désignation</th>
                                    <th class="px-6 py-4 text-center">Qté</th>
                                    <th class="px-6 py-4 text-right">P.U</th>
                                    <th class="px-6 py-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="line in activeInvoice.lines" :key="line.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-700">{{ line.product_name_snapshot }}</td>
                                    <td class="px-6 py-4 text-center font-black text-slate-900">{{ line.quantity }}</td>
                                    <td class="px-6 py-4 text-right text-slate-500">{{ parseFloat(line.unit_cost).toFixed(2) }} DH</td>
                                    <td class="px-6 py-4 text-right font-black text-slate-900">{{ parseFloat(line.total_line_cost).toFixed(2) }} DH</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Returns / Avoirs Section -->
                <div v-if="activeInvoice.returns && activeInvoice.returns.length > 0" class="mb-8 animate-in slide-in-from-bottom-2 duration-300">
                    <h4 class="text-xs font-black text-amber-600 uppercase tracking-[0.2em] mb-4 ml-2 flex items-center">
                        <ArrowLeftRightIcon class="w-4 h-4 mr-2" /> Retours & Avoirs
                    </h4>
                    <div class="space-y-3">
                        <div v-for="ret in activeInvoice.returns" :key="ret.id" class="bg-amber-50/50 rounded-2xl border border-amber-100 p-5 flex justify-between items-center group hover:bg-amber-50 transition-all">
                            <div>
                                <p class="font-black text-amber-800 text-sm">AVOIR #{{ ret.id.toString().padStart(4, '0') }}</p>
                                <p class="text-[10px] font-bold text-amber-600/70 mt-1 uppercase tracking-widest">Motif: {{ ret.reason || 'Non spécifié' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-amber-700 mb-1 uppercase">{{ parseFloat(ret.returned_quantity).toFixed(2) }} retournés</p>
                                <p class="font-black text-rose-500 text-base">- {{ safeNumber(ret.refund_amount) }} DH</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER / ACTIONS -->
            <div class="p-8 border-t border-slate-100 bg-white flex justify-between items-center">
                <div class="bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Reste en Stock</p>
                    <p class="font-black text-slate-900 text-lg leading-none">
                       <span v-if="activeInvoice.available_mdf > 0" class="mr-3">{{ parseFloat(activeInvoice.available_mdf).toFixed(2) }} <span class="text-[10px] text-slate-400 uppercase">Plaques</span></span>
                       <span v-if="activeInvoice.available_canto > 0" class="mr-3">{{ parseFloat(activeInvoice.available_canto).toFixed(2) }} <span class="text-[10px] text-slate-400 uppercase">Mètres</span></span>
                       <span v-if="activeInvoice.available_consumable > 0">{{ parseFloat(activeInvoice.available_consumable).toFixed(2) }} <span class="text-[10px] text-slate-400 uppercase">Unités</span></span>
                       <span v-if="activeInvoice.available_mdf == 0 && activeInvoice.available_canto == 0 && activeInvoice.available_consumable == 0">0</span>
                    </p>
                </div>
                <div class="flex gap-4">
                    <button v-if="activeInvoice.available_qty > 0" @click="triggerReturnFromInvoice" class="px-8 py-4 bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white font-black rounded-2xl text-xs uppercase tracking-widest transition-all shadow-sm active:scale-95 flex items-center">
                        <ArrowLeftRightIcon class="w-4 h-4 mr-2" /> Retourner
                    </button>
                    <button class="px-8 py-4 bg-slate-900 text-white hover:bg-slate-800 font-black rounded-2xl text-xs uppercase tracking-widest transition-all shadow-xl shadow-slate-900/20 active:scale-95 flex items-center">
                        <FileDownIcon class="w-4 h-4 mr-2" /> Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Return Modal -->
    <div v-if="showReturnModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-[32px] w-full max-w-md overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
        <div class="p-6 border-b border-slate-100 bg-amber-50/50 flex justify-between items-center">
          <h3 class="font-black text-lg text-amber-800 uppercase tracking-tight flex items-center">
            <ArrowLeftRightIcon class="w-6 h-6 mr-3" /> Retour Fournisseur
          </h3>
          <button @click="closeReturnModal" class="text-amber-400 hover:text-amber-600 transition-colors">
            <XIcon class="w-6 h-6" />
          </button>
        </div>
        
        <div class="p-8">
          <!-- Dynamic Unit Logic -->
          <div class="mb-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Article</p>
              <p class="font-black text-slate-800 text-base">{{ selectedPurchase.item_name }}</p>
              <p class="text-[10px] font-bold text-slate-500 mt-1">
                  Acheté: <span class="font-black text-slate-700">{{ parseFloat(selectedPurchase.original_qty).toFixed(2) }}</span> 
                  <span class="uppercase ml-1">{{ selectedPurchase.item_name.toLowerCase().includes('canto') || selectedPurchase.item_name.toLowerCase().includes('bandchant') ? 'mètres' : 'pièces' }}</span>
              </p>
          </div>

          <div v-if="selectedPurchase.lines?.length > 1" class="mb-6">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Choisir l'article à retourner</label>
            <select v-model="returnForm.purchase_line_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:border-amber-500 font-bold text-slate-700">
              <option v-for="line in selectedPurchase.lines" :key="line.id" :value="line.id">
                {{ line.product_name_snapshot }} ({{ line.quantity }} achetés)
              </option>
            </select>
          </div>

          <div class="mb-6">
            <div class="flex justify-between items-end mb-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Quantité à retourner</label>
                <span class="text-[10px] font-black text-brand-600 uppercase tracking-wider">
                    Dispo: {{ parseFloat(selectedPurchase.available_qty).toFixed(2) }}
                </span>
            </div>
            
            <div class="relative">
                <input type="number" 
                       v-model="returnForm.returned_quantity" 
                       :step="selectedPurchase.item_name.toLowerCase().includes('canto') || selectedPurchase.item_name.toLowerCase().includes('bandchant') ? '0.1' : '1'"
                       :max="selectedPurchase.available_qty" 
                       min="0" 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:border-amber-500 font-black text-slate-800 text-lg"
                       placeholder="0.00">
                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-[10px] uppercase tracking-widest">
                     {{ selectedPurchase.item_name.toLowerCase().includes('canto') || selectedPurchase.item_name.toLowerCase().includes('bandchant') ? 'M' : 'PCS' }}
                </div>
            </div>
            
            <div class="mt-4 flex justify-between items-center px-1 pt-3 border-t border-slate-100">
               <span class="text-[10px] font-bold text-slate-400 uppercase">Remboursement estimé:</span>
               <span class="text-sm font-black text-amber-600">{{ estimatedRefund }} DH</span>
            </div>
          </div>

          <div class="mb-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Motif du retour</label>
            <textarea v-model="returnForm.reason" rows="3" 
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:border-amber-500 font-bold text-slate-700" 
              placeholder="Ex: Plaques endommagées, erreur de référence..."></textarea>
          </div>
        </div>

        <div class="p-6 bg-slate-50/50 flex gap-3">
          <button @click="closeReturnModal" class="flex-1 px-4 py-3 font-black text-slate-600 uppercase tracking-widest text-[10px] hover:bg-slate-100 rounded-2xl transition-all">
            Annuler
          </button>
          <button @click="submitReturn" :disabled="isSubmittingReturn || !returnForm.returned_quantity" 
            class="flex-1 px-4 py-3 font-black text-white bg-amber-500 hover:bg-amber-600 rounded-2xl transition-all uppercase tracking-widest text-[10px] shadow-lg shadow-amber-200 flex items-center justify-center disabled:opacity-50">
            <Loader2Icon v-if="isSubmittingReturn" class="w-3 h-3 mr-2 animate-spin" />
            <CheckCircleIcon v-else class="w-3 h-3 mr-2" />
            Confirmer le retour
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
// Track which purchase row is expanded
const expandedRow = ref(null);

// Toggle expansion
const toggleExpand = (id) => {
    expandedRow.value = expandedRow.value === id ? null : id;
};

// State for Filters & View
const viewMode = ref('list'); // 'list' or 'grid'
const startDate = ref('');
const endDate = ref('');

import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  HistoryIcon, RotateCwIcon, AlertCircleIcon, CalendarIcon, 
  FileTextIcon, TruckIcon, SearchIcon, ArrowLeftRightIcon, XIcon, CheckCircleIcon, Loader2Icon, ArrowRightIcon, BanIcon,
  LayoutGridIcon, ListIcon, CalendarDaysIcon, XCircleIcon, CornerDownRightIcon, ReceiptIcon, EyeIcon, FileDownIcon
} from 'lucide-vue-next';

const purchases = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

// Modal state
const showReturnModal = ref(false);
const isSubmittingReturn = ref(false);
const selectedPurchase = ref(null);
const returnForm = ref({
    purchase_line_id: null,
    returned_quantity: '',
    reason: ''
});

// NEW: Invoice Details Modal state
const showInvoiceModal = ref(false);
const activeInvoice = ref(null);

const openInvoiceModal = (purchase) => {
    activeInvoice.value = purchase;
    showInvoiceModal.value = true;
};

const closeInvoiceModal = () => {
    showInvoiceModal.value = false;
    activeInvoice.value = null;
};

const triggerReturnFromInvoice = () => {
    openReturnModal(activeInvoice.value);
    // Don't close invoice modal, maybe user wants to return to it after?
    // Actually, usually we close it to focus on return.
    showInvoiceModal.value = false;
};

const loadPurchases = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const res = await axios.get('/api/admin/purchases/history');
    purchases.value = Array.isArray(res.data) ? res.data : [];
  } catch (error) {
    console.error('API Fetch Error:', error);
    errorMessage.value = 'Impossible de charger l\'historique des achats.';
    purchases.value = [];
  } finally {
    isLoading.value = false;
  }
};

// Computed property for filtering
const filteredPurchases = computed(() => {
    return purchases.value.filter(purchase => {
        if (!startDate.value && !endDate.value) return true;
        
        const pDate = new Date(purchase.raw_date || purchase.created_at);
        
        const start = startDate.value ? new Date(startDate.value) : new Date('2000-01-01');
        const end = endDate.value ? new Date(endDate.value) : new Date('2100-01-01');
        // Set end time to end of day
        end.setHours(23, 59, 59, 999);

        return pDate >= start && pDate <= end;
    });
});

const openReturnModal = (purchase) => {
    selectedPurchase.value = purchase;
    returnForm.value.purchase_line_id = purchase.lines?.[0]?.id || null;
    returnForm.value.returned_quantity = '';
    returnForm.value.reason = '';
    showReturnModal.value = true;
};

const closeReturnModal = () => {
    showReturnModal.value = false;
    selectedPurchase.value = null;
};

const estimatedRefund = computed(() => {
    if (!selectedPurchase.value || !returnForm.value.purchase_line_id) return '0.00';
    const line = selectedPurchase.value.lines.find(l => l.id === returnForm.value.purchase_line_id);
    if (!line) return '0.00';
    const qty = parseFloat(returnForm.value.returned_quantity) || 0;
    return (qty * parseFloat(line.unit_cost)).toFixed(2);
});

const submitReturn = async () => {
    isSubmittingReturn.value = true;
    try {
        const res = await axios.post(`/api/admin/purchases/${selectedPurchase.value.id}/return`, returnForm.value);
        toast.success(res.data.message);
        closeReturnModal();
        loadPurchases();
    } catch (error) {
        toast.error(error.response?.data?.error || 'Erreur lors du retour.');
    } finally {
        isSubmittingReturn.value = false;
    }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

const safeNumber = (val) => {
  const num = parseFloat(val);
  return isNaN(num) ? '0.00' : num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getPaymentStatusClass = (paid, total) => {
  const p = parseFloat(paid) || 0;
  const t = parseFloat(total) || 0;
  if (p < t) return 'bg-rose-50 text-rose-600 border border-rose-100';
  return 'bg-emerald-50 text-emerald-600 border border-emerald-100';
};

onMounted(() => loadPurchases());
</script>

<style scoped>
.shadow-soft { box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
