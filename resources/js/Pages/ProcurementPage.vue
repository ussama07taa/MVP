<template>
  <div class="max-w-6xl mx-auto pb-10">
    <header class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center">
          <TruckIcon class="w-6 h-6 mr-3 text-brand-600"/> Réception Fournisseur (Achats)
        </h1>
        <p class="text-slate-500 text-sm mt-1">Enregistrez vos factures d'achat, le stock et les crédits fournisseurs.</p>
      </div>
      
      <div class="flex items-center gap-4">
        <!-- Actualiser Button (Master UX) -->
        <button @click="loadAllData" 
          :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
          class="group relative p-3.5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-300 transition-all duration-300 active:scale-90"
          title="Actualiser">
          <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-brand-600 transition-transform duration-500" />
        </button>

        <button @click="showSupplierHistory" :disabled="!form.supplier_id" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all flex items-center disabled:opacity-30">
          <HistoryIcon class="w-4 h-4 mr-2" /> Historique & Crédit
        </button>
      </div>
    </header>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <!-- Section 1: Infos Fournisseur -->
      <div class="p-6 border-b border-slate-100 bg-slate-50">
        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4">1. Informations de la Facture</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Fournisseur</label>
            <div class="flex space-x-2">
              <select v-model="form.supplier_id" @change="onSupplierChange" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm font-bold">
                <option value="">-- Sélectionner --</option>
                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }} (Dette: {{ sup.total_debt }} DH)</option>
              </select>
              <button @click="isSupplierModalOpen = true" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold rounded-lg hover:bg-slate-300 transition-colors flex items-center justify-center">
                <PlusIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">N° de Facture / Bon (Optionnel)</label>
            <input type="text" v-model="form.reference_invoice" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm" placeholder="EX: FAC-2026-089">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Photo / Scan de la Facture</label>
            <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition-all">
          </div>
        </div>
      </div>

      <!-- Section 2: ARTICLES ACHETÉS (STOCK) -->
      <div class="p-6">
          <div class="flex justify-between items-center mb-6">
              <h3 class="text-sm font-black text-slate-700 uppercase tracking-wider">2. Articles Achetés (Facture)</h3>
              <button @click="addItem" class="px-5 py-2.5 bg-brand-600 text-white hover:bg-brand-700 font-black rounded-xl text-sm transition-all shadow-md shadow-brand-600/20 flex items-center">
                  <PlusIcon class="w-4 h-4 mr-2" /> Ajouter Article
              </button>
          </div>

          <div class="space-y-6">
              <!-- Loop through each article in the cart -->
              <div v-for="(item, index) in cart" :key="index" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden group">
                  
                  <!-- CARD HEADER (Category Selection & Delete) -->
                  <div class="bg-slate-50 border-b border-slate-200 p-4 flex justify-between items-center">
                      <div class="flex items-center space-x-4">
                          <span class="flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-lg font-black text-slate-500 shadow-sm">
                              {{ index + 1 }}
                          </span>
                          <div class="w-64">
                              <select v-model="item.category" @change="resetItemData(index)" class="w-full p-2 text-sm font-bold border border-slate-300 rounded-lg focus:ring-brand-500 bg-white">
                                  <option value="mdf">Panneau (MDF / LATI)</option>
                                  <option value="canto">Bandchant (Rouleaux)</option>
                                  <option value="consumable">Consommable (Colle..)</option>
                              </select>
                          </div>
                      </div>
                      <button @click="removeItem(index)" class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-white hover:bg-red-500 bg-white border border-red-100 rounded-lg transition-colors shadow-sm">
                          <Trash2Icon class="w-4 h-4" />
                      </button>
                  </div>

                  <!-- CARD BODY (The Exact Full Forms) -->
                  <div class="p-6">
                      
                      <!-- FORMULAIRE MDF / LATI -->
                      <div v-if="item.category === 'mdf'" class="animate-in fade-in duration-500">
                          <!-- Product Selection / Creation Row -->
                          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                               <div>
                                   <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Type / Nom</label>
                                   <input type="text" v-model="item.data.type" placeholder="Ex: MDF" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500 font-bold">
                               </div>
                               <div>
                                   <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Finition</label>
                                   <input type="text" v-model="item.data.finish_type" placeholder="Ex: CH" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500">
                               </div>
                               <div>
                                   <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Catalogue / Marque</label>
                                   <input type="text" v-model="item.data.provider_catalog" placeholder="Ex: SONAE" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500">
                               </div>
                          </div>

                          <!-- Palette Picker -->
                          <div class="mb-6 p-6 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                              <WoodTexturePicker v-model="item.data.color_code" @textureSelected="(t) => { item.data.color_name = t.name; if(!item.data.type) item.data.type = 'MDF ' + t.name.toUpperCase(); }" />
                              <div class="mt-4">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nom de la couleur (Désignation)</label>
                                <input type="text" v-model="item.data.color_name" placeholder="ex: CHENE TERRA" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500 font-bold">
                              </div>
                          </div>

                          <!-- Dimensions, Qty, Prices -->
                          <div class="grid grid-cols-2 md:grid-cols-5 gap-4 items-end">
                              <div>
                                   <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ép (MM)</label>
                                   <input type="number" v-model="item.data.thickness" class="w-full p-3 border border-slate-300 rounded-xl text-center font-bold">
                              </div>
                              <div>
                                   <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dim (LxH)</label>
                                   <div class="flex gap-1">
                                      <input type="number" v-model="item.data.size_x" class="w-1/2 p-3 border border-slate-300 rounded-xl text-center font-bold text-xs">
                                      <input type="number" v-model="item.data.size_y" class="w-1/2 p-3 border border-slate-300 rounded-xl text-center font-bold text-xs">
                                   </div>
                              </div>
                              <div>
                                   <label class="block text-[10px] font-black text-emerald-600 uppercase mb-1">Qté (Achat)</label>
                                   <input type="number" v-model="item.data.quantity" min="1" class="w-full p-3 border border-emerald-300 bg-emerald-50 text-emerald-900 rounded-xl text-center font-black">
                              </div>
                              <div>
                                   <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Prix U. (Achat)</label>
                                   <input type="number" step="0.01" v-model="item.unit_cost" class="w-full p-3 border border-slate-300 rounded-xl text-right font-bold">
                              </div>
                              <div>
                                   <label class="block text-[10px] font-bold text-brand-600 uppercase mb-1">Prix Vente</label>
                                   <input type="number" step="0.01" v-model="item.data.base_price_sell" class="w-full p-3 border border-brand-100 bg-brand-50 text-brand-900 rounded-xl text-right font-black">
                              </div>
                          </div>
                          
                          <!-- Line Total -->
                          <div class="mt-4 text-right bg-slate-50 p-3 rounded-xl border border-slate-200">
                              <span class="text-xs font-bold text-slate-500 uppercase mr-3">Total Ligne (DH):</span>
                              <span class="text-xl font-black text-slate-800">{{ ((item.data.quantity || 0) * (item.unit_cost || 0)).toFixed(2) }}</span>
                          </div>
                      </div>

                      <!-- FORMULAIRE BANDCHANT -->
                      <div v-else-if="item.category === 'canto'" class="animate-in fade-in duration-500">
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                               <div>
                                   <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nom du Canto</label>
                                   <input type="text" v-model="item.data.name" placeholder="Ex: BANDCHANT HALIFAX" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500 font-bold">
                               </div>
                               <div>
                                   <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Code / Couleur</label>
                                   <input type="text" v-model="item.data.color_name" placeholder="Ex: H1181" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500 font-bold">
                               </div>
                          </div>

                          <!-- Palette Picker for Canto -->
                          <div class="mb-6 p-6 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                              <WoodTexturePicker v-model="item.data.color_code" @textureSelected="(t) => { item.data.color_name = t.name; if(!item.data.name) item.data.name = 'BANDCHANT ' + t.name.toUpperCase(); }" />
                          </div>

                          <!-- Quantities & Prices -->
                          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
                              <div>
                                   <label class="block text-[10px] font-black text-emerald-600 uppercase mb-1">Nbr Rouleaux</label>
                                   <input type="number" v-model="item.data.rolls" min="1" class="w-full p-3 border border-emerald-300 bg-emerald-50 text-emerald-900 rounded-xl text-center font-black">
                              </div>
                              <div>
                                   <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Mètres / Rouleau</label>
                                   <input type="number" v-model="item.data.meters_per_roll" class="w-full p-3 border border-slate-300 rounded-xl text-center font-bold">
                              </div>
                              <div>
                                   <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Prix Achat (DH/m)</label>
                                   <input type="number" step="0.01" v-model="item.unit_cost" class="w-full p-3 border border-slate-300 rounded-xl text-right font-bold">
                              </div>
                              <div>
                                   <label class="block text-[10px] font-bold text-brand-600 uppercase mb-1">Prix Vente (DH/m)</label>
                                   <input type="number" step="0.01" v-model="item.data.base_price_sell_per_m" class="w-full p-3 border border-brand-100 bg-brand-50 text-brand-900 rounded-xl text-right font-black">
                              </div>
                          </div>

                          <!-- Line Total -->
                          <div class="mt-4 flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-200">
                              <div class="text-xs font-bold text-emerald-600 uppercase">
                                  Stock: {{ (item.data.rolls || 0) * (item.data.meters_per_roll || 0) }} Mètres Linéaires
                              </div>
                              <div>
                                  <span class="text-xs font-bold text-slate-500 uppercase mr-3">Coût Ligne (DH):</span>
                                  <span class="text-xl font-black text-slate-800">
                                      {{ (((item.data.rolls || 0) * (item.data.meters_per_roll || 0)) * (item.unit_cost || 0)).toFixed(2) }}
                                  </span>
                              </div>
                          </div>
                      </div>

                      <!-- FORMULAIRE CONSOMMABLE -->
                      <div v-else-if="item.category === 'consumable'" class="animate-in fade-in duration-500">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                             <div>
                                 <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nom de l'article</label>
                                 <input type="text" v-model="item.data.name" placeholder="Ex: Colle" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500 font-bold">
                             </div>
                             <div>
                                 <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Unité</label>
                                 <select v-model="item.data.unit" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-brand-500 font-bold">
                                    <option value="KG">Kilogramme (KG)</option>
                                    <option value="L">Litre (L)</option>
                                    <option value="Piece">Pièce / Boite</option>
                                 </select>
                             </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                 <label class="block text-[10px] font-black text-emerald-600 uppercase mb-1">Qté Achetée</label>
                                 <input type="number" v-model="item.data.quantity" class="w-full p-3 border border-emerald-300 bg-emerald-50 text-emerald-900 rounded-xl text-center font-black">
                            </div>
                            <div>
                                 <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Prix Unitaire</label>
                                 <input type="number" step="0.01" v-model="item.unit_cost" class="w-full p-3 border border-slate-300 rounded-xl text-right font-bold">
                            </div>
                            <div class="text-right flex flex-col justify-end">
                                <span class="text-xs font-bold text-slate-500 uppercase mb-1">Total Ligne:</span>
                                <span class="text-2xl font-black text-slate-800">{{ ((item.data.quantity || 0) * (item.unit_cost || 0)).toFixed(2) }} DH</span>
                            </div>
                        </div>
                      </div>

                  </div>
              </div>

              <!-- Empty State -->
              <div v-if="cart.length === 0" class="p-12 border-2 border-dashed border-slate-200 rounded-[32px] text-center bg-slate-50/50">
                  <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-slate-100">
                    <FileTextIcon class="text-4xl text-slate-300" />
                  </div>
                  <h4 class="text-xl font-black text-slate-700 mb-1 tracking-tight">La facture est vide</h4>
                  <p class="text-sm text-slate-500 mb-6 font-medium">Ajoutez des panneaux MDF ou des Bandchants à cette réception.</p>
                  <button @click="addItem" class="px-8 py-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold rounded-2xl shadow-sm transition-all active:scale-95">
                      <PlusIcon class="w-4 h-4 mr-2 inline" /> Ajouter le premier article
                  </button>
              </div>
          </div>
      </div>

      <!-- Section 3: Totals & Paiement -->
      <div class="p-6 bg-slate-800 text-white border-t border-slate-700">
        <div class="flex flex-col md:flex-row justify-between items-end gap-6">
          <div class="w-full md:w-1/2 space-y-4">
            <div>
              <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Total Facture Fournisseur</label>
              <div class="text-4xl font-black text-white leading-none">{{ computedTotalAmount.toFixed(2) }} <span class="text-lg text-slate-500">DH</span></div>
            </div>
            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-700">
              <label class="flex justify-between text-xs font-black text-slate-400 uppercase tracking-widest mb-3">
                <span>Avance Payée</span>
                <span v-if="remainingDebt > 0" class="text-rose-400">Nouveau Crédit: {{ remainingDebt.toFixed(2) }} DH</span>
              </label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500 font-black">DH</span>
                <input type="number" v-model="form.amount_paid" class="w-full pl-12 pr-4 py-4 bg-slate-800 border border-slate-600 rounded-xl text-white font-black text-2xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all">
              </div>
            </div>
          </div>
          
          <div class="w-full md:w-1/3">
            <button @click="submitPurchase" :disabled="!form.supplier_id || cart.length === 0" 
                    class="w-full bg-brand-600 hover:bg-brand-500 text-white font-black py-5 px-8 rounded-2xl shadow-xl shadow-brand-900/20 transition-all active:scale-95 disabled:opacity-30 flex justify-center items-center uppercase tracking-widest text-sm">
              <SaveIcon class="w-5 h-5 mr-3"/> Enregistrer l'achat
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: ADD SUPPLIER (HEADLESS UI) -->
    <TransitionRoot as="template" :show="isSupplierModalOpen">
      <Dialog as="div" class="relative z-50" @close="isSupplierModalOpen = false">
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-[32px] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg mx-4 sm:mx-auto border border-slate-100">
                <div class="bg-white px-8 pt-8 pb-6">
                  <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-50 sm:mx-0 sm:h-12 sm:w-12">
                      <TruckIcon class="h-7 w-7 text-brand-600" aria-hidden="true" />
                    </div>
                    <div class="mt-3 text-center sm:ml-6 sm:mt-0 sm:text-left w-full">
                      <DialogTitle as="h3" class="text-2xl font-black leading-6 text-slate-900 uppercase tracking-tight">Nouveau Fournisseur</DialogTitle>
                      <div class="mt-6 space-y-5">
                        <div>
                          <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nom / Raison Sociale</label>
                          <input type="text" v-model="newSupplier.name" class="block w-full rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-brand-500 font-bold">
                        </div>
                        <div>
                          <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Téléphone</label>
                          <input type="text" v-model="newSupplier.phone" class="block w-full rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-brand-500 font-bold">
                        </div>
                        <div>
                          <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Dette Initiale (DH)</label>
                          <input type="number" v-model="newSupplier.total_debt" class="block w-full rounded-xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-brand-500 font-black text-brand-600">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="bg-slate-50 px-8 py-6 sm:flex sm:flex-row-reverse gap-3">
                  <button type="button" @click="saveNewSupplier" class="inline-flex w-full justify-center rounded-xl bg-brand-600 px-8 py-4 text-sm font-black text-white shadow-lg hover:bg-brand-500 sm:w-auto uppercase tracking-widest transition-all active:scale-95">Enregistrer</button>
                  <button type="button" @click="isSupplierModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-8 py-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-100 sm:mt-0 sm:w-auto transition-all">Annuler</button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- MODAL: SUPPLIER HISTORY -->
    <TransitionRoot as="template" :show="isHistoryModalOpen">
      <Dialog as="div" class="relative z-50" @close="isHistoryModalOpen = false">
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" />
        </TransitionChild>
        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-[32px] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-5xl border border-slate-100">
                <div class="bg-white px-8 pt-8 pb-6">
                  <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tight">Analyse Fournisseur: {{ selectedSupplierName }}</h3>
                        <p class="text-slate-500 font-bold">Récapitulatif des volumes et factures</p>
                    </div>
                    <div class="bg-rose-50 px-6 py-3 rounded-2xl border border-rose-100 text-right">
                       <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Dette Actuelle</span>
                       <span class="text-2xl font-black text-rose-600">{{ selectedSupplierDebt }} DH</span>
                    </div>
                  </div>

                  <!-- VOLUME SUMMARY CARDS -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                     <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-2xl flex items-center">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-xl mr-4">
                           <LayersIcon class="w-6 h-6"/>
                        </div>
                        <div>
                           <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Total Panneaux Achetés</p>
                           <p class="text-xl font-black text-blue-700">{{ historySummary.total_panels }} <span class="text-xs">Pièces</span></p>
                        </div>
                     </div>
                     <div class="bg-amber-50/50 border border-amber-100 p-4 rounded-2xl flex items-center">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl mr-4">
                           <PaletteIcon class="w-6 h-6"/>
                        </div>
                        <div>
                           <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Total Chants Achetés</p>
                           <p class="text-xl font-black text-amber-700">{{ historySummary.total_cantos }} <span class="text-xs">Mètres</span></p>
                        </div>
                     </div>
                  </div>

                  <div class="overflow-x-auto max-h-[55vh] custom-scrollbar pr-2">
                    <div v-for="purch in purchaseHistory" :key="purch.id" class="mb-6 border border-slate-100 rounded-[24px] overflow-hidden shadow-sm">
                        <!-- Invoice Header -->
                        <div class="bg-slate-50/80 px-6 py-4 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors" @click="toggleInvoice(purch.id)">
                            <div class="flex items-center gap-4">
                                <div class="bg-white p-2 rounded-xl border border-slate-200">
                                    <FileTextIcon class="w-5 h-5 text-slate-400" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                      <p class="text-sm font-black text-slate-900">Facture: {{ purch.reference_invoice || 'SANS RÉF' }}</p>
                                      <a v-if="purch.document_path" :href="'/storage/' + purch.document_path" target="_blank" class="p-1.5 bg-brand-50 text-brand-600 rounded-lg hover:bg-brand-100 transition-colors" @click.stop title="Voir le document">
                                        <ExternalLinkIcon class="w-3 h-3" />
                                      </a>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ new Date(purch.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Total</p>
                                    <p class="text-sm font-black text-slate-900">{{ purch.total_amount }} DH</p>
                                </div>
                                <ChevronDownIcon :class="expandedInvoices.includes(purch.id) ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" />
                            </div>
                        </div>

                        <!-- Invoice Details (Items & Payments) -->
                        <div v-if="expandedInvoices.includes(purch.id)" class="bg-white">
                            <!-- Items Section -->
                            <div class="px-6 py-4">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Détail des Articles</h4>
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead>
                                        <tr>
                                            <th class="py-2 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Article</th>
                                            <th class="py-2 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Quantité</th>
                                            <th class="py-2 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">P.U Achat</th>
                                            <th class="py-2 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="item in purch.items" :key="item.id">
                                            <td class="py-2">
                                                <div class="flex items-center">
                                                    <div :class="getItemColor(item.category)" class="w-2 h-2 rounded-full mr-3"></div>
                                                    <span class="text-sm font-bold text-slate-700">{{ item.item_name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-2 text-center text-sm font-black text-slate-900">{{ item.quantity }}</td>
                                            <td class="py-2 text-right text-sm font-bold text-slate-500">{{ item.unit_price }} DH</td>
                                            <td class="py-2 text-right text-sm font-black text-slate-900">{{ item.total_price }} DH</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Payments Section -->
                            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Historique des Règlements</h4>
                                <div class="space-y-2">
                                    <div v-for="pay in purch.payments" :key="pay.id" class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mr-3">
                                                <CheckCircleIcon class="w-4 h-4"/>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-slate-800">Paiement {{ pay.payment_method }}</p>
                                                <p class="text-[10px] font-bold text-slate-400">{{ new Date(pay.created_at).toLocaleDateString() }}</p>
                                            </div>
                                        </div>
                                        <p class="text-sm font-black text-emerald-600">+ {{ pay.amount }} DH</p>
                                    </div>
                                    <div v-if="purch.payments.length === 0" class="text-center py-2 text-[10px] font-bold text-slate-400 uppercase">Aucun règlement spécifique</div>
                                </div>
                            </div>

                            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-8 text-right bg-slate-50">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Total Payé</p>
                                    <p class="text-sm font-black text-emerald-600">{{ purch.amount_paid }} DH</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Reste à Payer</p>
                                    <p class="text-sm font-black text-rose-600">{{ (purch.total_amount - purch.amount_paid).toFixed(2) }} DH</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="purchaseHistory.length === 0" class="py-20 text-center text-slate-300 font-black uppercase tracking-widest">
                       Aucun achat enregistré
                    </div>
                  </div>
                </div>
                <div class="bg-slate-50 px-8 py-6 flex justify-end">
                  <button type="button" @click="isHistoryModalOpen = false" class="rounded-2xl bg-white px-4 md:px-10 py-4 text-sm font-black text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-100 uppercase tracking-widest transition-all active:scale-95">Fermer</button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
const toast = useToast();
import { 
  TruckIcon, PlusCircleIcon, PackagePlusIcon, Trash2Icon, SaveIcon, PlusIcon, 
  HistoryIcon, LayersIcon, PaletteIcon, FileTextIcon, ChevronDownIcon, CheckCircleIcon,
  ExternalLinkIcon, RotateCwIcon
} from 'lucide-vue-next';
import WoodTexturePicker from '@/Components/WoodTexturePicker.vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const suppliers = ref([]);
const existingPanels = ref([]);
const existingCantos = ref([]);
const isLoading = ref(false);
const cart = ref([]);
const form = ref({ supplier_id: '', reference_invoice: '', amount_paid: null });
const invoiceFile = ref(null);

const handleFileUpload = (event) => {
  invoiceFile.value = event.target.files[0];
};

// Modal State
const isSupplierModalOpen = ref(false);
const isHistoryModalOpen = ref(false);
const newSupplier = ref({ name: '', phone: '', total_debt: 0 });
const purchaseHistory = ref([]);
const expandedInvoices = ref([]);
const historySummary = ref({ total_panels: 0, total_cantos: 0 });

const selectedSupplierName = computed(() => {
  const s = suppliers.value.find(sup => sup.id === form.value.supplier_id);
  return s ? s.name : '';
});

const selectedSupplierDebt = computed(() => {
  const s = suppliers.value.find(sup => sup.id === form.value.supplier_id);
  return s ? s.total_debt : 0;
});

const fetchData = async () => {
  isLoading.value = true;
  try {
    const [sRes, pRes, cRes] = await Promise.all([
      axios.get('/api/admin/suppliers'),
      axios.get('/api/panels'),
      axios.get('/api/admin/cantos')
    ]);
    suppliers.value = sRes.data;
    existingPanels.value = pRes.data;
    existingCantos.value = cRes.data;
  } catch (e) {
    console.error("Fetch Error:", e);
  } finally {
    isLoading.value = false;
  }
};

const onSupplierChange = () => {
  // Logic if needed
};

const refreshHistory = async () => {
  if (!form.value.supplier_id) return;
  try {
    const res = await axios.get(`/api/admin/suppliers/${form.value.supplier_id}/history`);
    purchaseHistory.value = res.data.purchases;
    historySummary.value = res.data.summary;
  } catch (e) {
    console.error("Refresh History Error:", e);
  }
};

const showSupplierHistory = async () => {
  if (!form.value.supplier_id) return;
  expandedInvoices.value = [];
  try {
    await refreshHistory();
    isHistoryModalOpen.value = true;
    if(purchaseHistory.value.length > 0) {
        expandedInvoices.value.push(purchaseHistory.value[0].id);
    }
  } catch (e) {
    toast.error('Impossible de charger l\'historique');
  }
};

const toggleInvoice = (id) => {
  const index = expandedInvoices.value.indexOf(id);
  if (index > -1) {
    expandedInvoices.value.splice(index, 1);
  } else {
    expandedInvoices.value.push(id);
  }
};

const getItemColor = (cat) => {
  if (cat === 'mdf' || cat === 'panel') return 'bg-blue-500';
  if (cat === 'canto') return 'bg-amber-500';
  return 'bg-emerald-500';
};

const saveNewSupplier = async () => {
  if(!newSupplier.value.name) return toast.warning('Le nom est requis');
  try {
    const res = await axios.post('/api/admin/suppliers', newSupplier.value);
    suppliers.value.push(res.data);
    form.value.supplier_id = res.data.id;
    isSupplierModalOpen.value = false;
    newSupplier.value = { name: '', phone: '', total_debt: 0 };
  } catch (error) {
    toast.error('Erreur lors de la création du fournisseur');
  }
};

const addItem = () => {
  cart.value.push({
    category: 'mdf',
    unit_cost: 0,
    data: { 
      existing_id: null,
      type: '', 
      finish_type: '', 
      color_code: '', 
      color_name: '',
      provider_catalog: '', 
      size_x: 2800, 
      size_y: 2100, 
      thickness: 18, 
      quantity: 1, 
      base_price_sell: 0,
      // specific to Canto
      rolls: 1,
      meters_per_roll: 150
    }
  });
};

const selectExistingItem = (index, event) => {
  const selectedId = event.target.value;
  if (!selectedId) {
    resetItemData(index);
    return;
  }
  
  const cat = cart.value[index].category;
  if (cat === 'mdf') {
    const panel = existingPanels.value.find(p => p.id == selectedId);
    if (panel) {
      cart.value[index].data = {
        existing_id: panel.id,
        type: panel.type,
        finish_type: panel.finish_type,
        color_code: panel.color_code,
        color_name: panel.color_name,
        provider_catalog: panel.provider_catalog,
        size_x: panel.size_x,
        size_y: panel.size_y,
        thickness: panel.thickness,
        quantity: 1,
        base_price_sell: panel.base_price_sell
      };
    }
  }
  if (cat === 'canto') {
    const canto = existingCantos.value.find(c => c.id == selectedId);
    if (canto) {
      cart.value[index].data = {
        existing_id: canto.id,
        name: canto.name,
        color_code: canto.color_code,
        color_name: canto.color_name,
        finish_type: canto.finish_type,
        provider_catalog: canto.provider_catalog,
        width_mm: canto.width_mm,
        thickness_mm: canto.thickness_mm,
        total_length_remaining: 1,
        base_price_sell_per_m: canto.base_price_sell_per_m,
        rolls: 1,
        meters_per_roll: 150
      };
    }
  }
};

const calculateTotal = (index) => {
  const item = items.value[index];
  let qty = 0;
  if (item.category === 'mdf') qty = item.data.quantity;
  if (item.category === 'canto') qty = item.data.total_length_remaining;
  if (item.category === 'consumable') qty = item.data.quantity;
  
  item.total_cost = (qty * (item.unit_cost || 0)).toFixed(2);
};

const calculateUnit = (index) => {
  const item = items.value[index];
  let qty = 0;
  if (item.category === 'mdf') qty = item.data.quantity;
  if (item.category === 'canto') qty = item.data.total_length_remaining;
  if (item.category === 'consumable') qty = item.data.quantity;
  
  if (qty > 0) {
    item.unit_cost = (item.total_cost / qty).toFixed(2);
  }
};

const resetItemData = (index) => {
  const cat = cart.value[index].category;
  cart.value[index].unit_cost = 0;
  if (cat === 'mdf') {
    cart.value[index].data = { existing_id: null, type: '', finish_type: '', color_code: '', color_name: '', provider_catalog: '', size_x: 2800, size_y: 2100, thickness: 18, quantity: 1, base_price_sell: 0 };
  } else if (cat === 'canto') {
    cart.value[index].data = { existing_id: null, name: 'BANDCHANT PVC', color_code: '', color_name: '', finish_type: '', provider_catalog: '', width_mm: 22, thickness_mm: 0.8, total_length_remaining: 150, base_price_sell_per_m: 6, rolls: 1, meters_per_roll: 150 };
  } else if (cat === 'consumable') {
    cart.value[index].data = { name: '', unit: 'KG', quantity: 1 };
  }
};

const removeItem = (index) => cart.value.splice(index, 1);

const computedTotalAmount = computed(() => {
  return cart.value.reduce((total, item) => {
    let cost = 0;
    if (item.category === 'mdf') {
      cost = (item.data.quantity || 0) * (item.unit_cost || 0);
    } else if (item.category === 'canto') {
      const totalMeters = (item.data.rolls || 0) * (item.data.meters_per_roll || 0);
      cost = totalMeters * (item.unit_cost || 0);
    } else if (item.category === 'consumable') {
      cost = (item.data.quantity || 0) * (item.unit_cost || 0);
    }
    return total + cost;
  }, 0);
});

const remainingDebt = computed(() => {
  let diff = computedTotalAmount.value - (form.value.amount_paid || 0);
  return diff > 0 ? diff : 0;
});

const submitPurchase = async () => {
  try {
    const payloadItems = cart.value.map(item => {
      let preparedData = { ...item.data };
      let tCost = 0;
      if (item.category === 'mdf') {
        tCost = (item.data.quantity || 0) * (item.unit_cost || 0);
        preparedData.cost_price = item.unit_cost;
      }
      if (item.category === 'canto') {
        const totalMeters = (item.data.rolls || 0) * (item.data.meters_per_roll || 0);
        tCost = totalMeters * (item.unit_cost || 0);
        preparedData.total_length_remaining = totalMeters; // Override with calculated total
        preparedData.cost_price_per_m = item.unit_cost;
      }
      if (item.category === 'consumable') {
        tCost = (item.data.quantity || 0) * (item.unit_cost || 0);
        preparedData.unit_cost = item.unit_cost;
      }
      return { category: item.category, data: preparedData, total_cost: tCost };
    });

    // Use FormData for file upload
    const formData = new FormData();
    formData.append('supplier_id', form.value.supplier_id);
    formData.append('reference_invoice', form.value.reference_invoice || '');
    formData.append('total_amount', computedTotalAmount.value);
    formData.append('amount_paid', form.value.amount_paid || 0);
    formData.append('items', JSON.stringify(payloadItems));
    
    if (invoiceFile.value) {
      formData.append('invoice_document', invoiceFile.value);
    }

    await axios.post('/api/admin/purchases', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    toast.success('Achat enregistré avec succès !');
    cart.value = []; 
    form.value.amount_paid = null; 
    form.value.reference_invoice = ''; 
    form.value.supplier_id = '';
    invoiceFile.value = null;
    fetchData();
  } catch(e) { 
    toast.error('Erreur lors de l\'enregistrement.'); 
  }
};

onMounted(() => fetchData());
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
