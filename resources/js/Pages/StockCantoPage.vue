<template>
  <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8 font-sans">
    
    <!-- Header & Search -->
    <div class="mb-12 flex flex-col lg:flex-row lg:items-end justify-between gap-8 animate-in fade-in slide-in-from-top duration-700">
      <div class="flex items-center space-x-6">
        <div class="relative group">
          <div class="absolute -inset-1 bg-gradient-to-tr from-emerald-600 to-teal-400 rounded-[32px] blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
          <div class="relative bg-white p-5 rounded-[28px] shadow-premium border border-slate-100">
             <PaletteIcon class="w-10 h-10 text-emerald-600" />
          </div>
        </div>
        <div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tight leading-none mb-3">Stock Bandchant</h1>
          <div class="flex items-center space-x-2">
            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <p class="text-slate-500 font-bold text-sm tracking-wide uppercase opacity-70">Gestion de Rouleaux Master</p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Search & Refresh Group -->
        <div class="flex items-center bg-white/60 backdrop-blur-xl p-2 rounded-[24px] border border-white shadow-glass gap-2">
           <div class="relative group flex-1 min-w-[200px] md:min-w-[300px]">
              <SearchIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors" />
              <input v-model="searchQuery" type="text" placeholder="Rechercher..." 
                     class="w-full pl-10 pr-4 py-3 bg-slate-50/50 border-transparent rounded-2xl focus:ring-0 focus:bg-white transition-all font-bold text-slate-700 text-sm">
           </div>
           
           <button @click="loadCantos" 
             :class="isLoading ? 'opacity-50' : ''"
             class="p-3 bg-white text-slate-400 hover:text-emerald-600 rounded-xl shadow-sm border border-slate-100 transition-all active:scale-90 group">
             <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 transition-transform duration-500" />
           </button>

           <div class="h-8 w-px bg-slate-200 mx-1"></div>

           <!-- View Switcher -->
           <div class="flex items-center p-1 bg-slate-100/50 rounded-xl">
             <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="p-2 rounded-lg transition-all duration-200">
               <LayoutGridIcon class="w-4 h-4" />
             </button>
             <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="p-2 rounded-lg transition-all duration-200 ml-1">
               <ListIcon class="w-4 h-4" />
             </button>
           </div>
        </div>

        <div class="flex items-center gap-2">
            <button @click="openAdjustmentModal" class="p-4 bg-white text-rose-500 hover:bg-rose-50 rounded-[22px] shadow-sm border border-slate-100 transition-all active:scale-95 group" title="Déclarer Casse">
              <i class="fa-solid fa-crack text-lg"></i>
            </button>
            
            <button @click="exportData('canto')" class="p-4 bg-white text-emerald-600 hover:bg-emerald-50 rounded-[22px] shadow-sm border border-slate-100 transition-all active:scale-95 group" title="Exporter Excel">
              <FileDownIcon class="w-5 h-5" />
            </button>
            
            <button @click="triggerImport" 
                    class="p-4 bg-white text-indigo-600 hover:bg-indigo-50 rounded-[22px] shadow-sm border border-slate-100 transition-all active:scale-95 group" 
                    title="Importer Stock Initial (Colonnes: code, nom, marque, metrage, prix_achat, prix_vente, largeur, epaisseur)">
              <FileUpIcon class="w-5 h-5" />
            </button>
            <input type="file" ref="importInput" @change="handleImportFile" class="hidden" accept=".xlsx,.xls,.csv">
        </div>

        <button @click="toggleAddForm" class="relative group overflow-hidden bg-slate-900 text-white px-8 py-4 rounded-[24px] font-black shadow-xl hover:shadow-2xl transition-all active:scale-95">
          <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          <span class="relative flex items-center">
            <PlusCircleIcon class="w-5 h-5 mr-2" /> {{ showAddForm ? 'Fermer' : 'Nouveau Canto' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Add/Edit Form -->
    <transition name="fade-slide">
      <div v-if="showAddForm" class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-soft mb-8 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-2 h-full bg-emerald-500"></div>
        <h3 class="text-xl font-black mb-8 text-slate-800 flex items-center">
          <Edit3Icon v-if="editingId" class="w-5 h-5 mr-3 text-emerald-500" />
          {{ editingId ? 'Modifier le Rouleau' : 'Nouveau Rouleau en Stock' }}
        </h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Selection Article Existant -->
          <div class="lg:col-span-12 bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100 mb-4">
             <label class="text-[10px] font-black text-emerald-700 uppercase tracking-[0.2em] mb-3 block">Réceptionner un article déjà en stock ?</label>
             <select @change="handleExistingSelection($event)" class="w-full bg-white border-emerald-200 rounded-2xl p-4 font-black text-slate-700 shadow-sm focus:ring-emerald-500">
                <option value="">-- NOUVEAU PRODUIT (Créer une nouvelle fiche) --</option>
                <option v-for="c in (cantos || [])" :key="c?.id || Math.random()" :value="c?.id">
                   {{ c ? `#${c.id} - ${c.name} (${c.color_code}) - [Actuel: ${c.total_length_remaining}m]` : '' }}
                </option>
             </select>
             <p class="text-[9px] text-emerald-600 font-bold mt-2 px-1 italic">Sélectionner un article fusionnera le nouveau stock et calculera le nouveau Prix de Revient Moyen (CUMP).</p>
          </div>

          <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2 col-span-1 md:col-span-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type / Nom du Canto</label>
              <input v-model="form.name" type="text" placeholder="ex: BANDCHANT PVC" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Code Couleur</label>
              <input v-model="form.color_code" type="text" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nom de la Couleur (Designation)</label>
              <input v-model="form.color_name" type="text" placeholder="ex: CHENE TERRA" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Finition</label>
              <input v-model="form.finish_type" type="text" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Catalogue</label>
              <input v-model="form.provider_catalog" type="text" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-brand-500 font-black uppercase text-sm">
            </div>
            <!-- Secure Edit Section (Premium UX) -->
            <div v-if="editingId" class="md:col-span-2 mt-4 pt-8 border-t border-slate-100 animate-in fade-in slide-in-from-bottom duration-500">
               <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-stretch">
                  
                  <!-- AUDIT DATA (Locked) -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/50 p-6 rounded-[32px] border border-slate-200/50 shadow-inner">
                     <div class="space-y-1.5">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center">
                           <ShieldCheckIcon class="w-3 h-3 mr-1.5 text-slate-400" /> Stock Linéaire
                        </label>
                        <div class="flex items-baseline gap-1.5">
                           <span class="text-3xl font-black text-slate-700 tracking-tighter">{{ totalMeters }}</span>
                           <span class="text-[10px] font-bold text-slate-400 uppercase">Mètres</span>
                        </div>
                        <p class="text-[8px] text-slate-400 font-bold italic leading-tight">Total combiné des rouleaux</p>
                     </div>

                     <div class="space-y-1.5 sm:border-l sm:border-slate-200 sm:pl-6">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center">
                           <TrendingDownIcon class="w-3 h-3 mr-1.5 text-slate-400" /> Coût (CUMP)
                        </label>
                        <div class="flex items-baseline gap-1.5">
                           <span class="text-3xl font-black text-slate-700 tracking-tighter">{{ parseFloat(form.unit_cost_m).toFixed(2) }}</span>
                           <span class="text-[10px] font-bold text-slate-400 uppercase">DH/M</span>
                        </div>
                        <p class="text-[8px] text-slate-400 font-bold italic leading-tight">Valeur d'achat moyenne</p>
                     </div>
                  </div>

                  <!-- COMMERCIAL DATA (Editable) -->
                  <div class="bg-emerald-600 p-6 rounded-[32px] shadow-xl shadow-emerald-200 relative overflow-hidden group/sell">
                     <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover/sell:bg-white/20 transition-all duration-500"></div>
                     <label class="text-[10px] font-black text-emerald-100 uppercase tracking-[0.2em] flex items-center mb-4">
                        <TagIcon class="w-4 h-4 mr-2" /> Prix de Vente (m)
                     </label>
                     
                     <div class="relative">
                        <input v-model="form.sell_price_m" type="number" step="0.01" 
                               class="w-full bg-white/10 border-white/20 rounded-2xl p-4 font-black text-white text-3xl focus:ring-4 focus:ring-white/20 focus:border-white/40 shadow-inner transition-all placeholder:text-white/30"
                               placeholder="0.00">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-emerald-200 font-black">DH</span>
                     </div>
                     <p class="text-[9px] text-emerald-200 font-bold px-1 italic mt-3 flex items-center">
                        <InfoIcon class="w-3 h-3 mr-1.5" /> Prix unitaire au mètre.
                     </p>
                  </div>

               </div>
            </div>

            <div v-else class="md:col-span-2 contents">
               <!-- Refactored Quantity Section -->
               <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:col-span-2 bg-emerald-50/20 p-6 rounded-[32px] border border-emerald-100/50">
                  <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nombre de Rouleaux</label>
                  <input type="number" v-model="form.rolls" min="1" class="w-full bg-white border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-emerald-500 font-black text-sm">
                  </div>
                  <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Mètres / Rouleau</label>
                  <input type="number" v-model="form.meters_per_roll" min="1" class="w-full bg-white border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-emerald-500 font-black text-sm">
                  </div>
                  <div class="space-y-2">
                  <label class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Stock Total (Mètres)</label>
                  <div class="w-full bg-emerald-100/50 border border-emerald-200 rounded-2xl p-4 font-black text-emerald-800 text-sm flex justify-between items-center shadow-inner">
                     <span>{{ totalMeters }}</span>
                     <span class="text-[10px] font-bold opacity-50">ML</span>
                  </div>
                  </div>
               </div>

               <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:col-span-2">
                  <div class="space-y-2 bg-rose-50/30 p-6 rounded-[32px] border border-rose-100/50">
                  <label class="text-[10px] font-black text-rose-600 uppercase tracking-widest">Prix d'Achat (DH/m)</label>
                  <input v-model="form.unit_cost_m" type="number" step="0.01" class="w-full bg-white border-rose-100 rounded-2xl p-4 font-black text-rose-700 focus:ring-2 focus:ring-rose-500 text-sm">
                  <p class="text-[9px] text-rose-400 font-bold px-1 italic mt-2">Coût unitaire payé pour 1 mètre.</p>
                  </div>
                  
                  <div class="space-y-2 bg-brand-50/30 p-6 rounded-[32px] border border-brand-100/50">
                  <label class="text-[10px] font-black text-brand-600 uppercase tracking-widest">Prix de Vente (DH/m) *</label>
                  <input v-model="form.sell_price_m" type="number" step="0.01" class="w-full bg-white border-brand-100 rounded-2xl p-4 font-black text-brand-900 focus:ring-2 focus:ring-brand-500 text-sm">
                  <p class="text-[9px] text-brand-400 font-bold px-1 italic mt-2">Prix de vente appliqué en caisse.</p>
                  </div>

                  <!-- Coût Global Display -->
                  <div class="space-y-2 md:col-span-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Coût Global d'Achat (DH)</label>
                  <div class="w-full bg-slate-100 border border-slate-200 rounded-[32px] p-6 font-black text-slate-800 text-xl shadow-inner flex flex-col">
                     <div class="flex items-end gap-2">
                        <span>{{ globalCost }}</span>
                        <span class="text-sm font-bold text-slate-400 mb-1">DH</span>
                     </div>
                     <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-wide">Calcul: {{ totalMeters }}m × {{ form.unit_cost_m || 0 }} DH</p>
                  </div>
                  </div>

                  <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Avance Payée (DH)</label>
                  <input v-model="form.amount_paid" type="number" placeholder="Ex: 200" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 font-black text-slate-700 focus:ring-2 focus:ring-brand-500 text-sm">
                  </div>
                  <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Photo de la Facture (Optionnel)</label>
                  <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="block w-full text-[10px] text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 border border-slate-100 rounded-2xl p-3 bg-white">
                  </div>
               </div>
            </div>

            <!-- Supplier Selection -->
            <div class="space-y-2 md:col-span-2">
              <div class="flex justify-between items-center mb-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fournisseur</label>
                <button type="button" @click="showSupplierModal = true" class="text-[10px] font-black text-emerald-600 hover:text-emerald-800 flex items-center transition-colors">
                  <PlusCircleIcon class="w-3 h-3 mr-1" /> Nouveau Fournisseur
                </button>
              </div>
              <select v-model="form.supplier_id" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 font-black text-slate-700">
                <option :value="null">-- Sélectionner un fournisseur --</option>
                <option v-for="sup in suppliers" :key="sup?.id" :value="sup?.id">{{ sup?.name }}</option>
              </select>
            </div>
          </div>

          <div class="lg:col-span-4 bg-slate-50 rounded-[32px] p-6 border border-slate-100 flex flex-col justify-center">
            <WoodTexturePicker v-model="form.color_code" @textureSelected="(t) => { form.color_name = t.name; if(!form.name) form.name = 'BANDCHANT ' + t.name.toUpperCase(); }" />
          </div>
        </div>
        <div class="mt-8 flex justify-end">
          <button @click="saveCanto" class="bg-emerald-600 text-white px-12 py-4 rounded-2xl font-black shadow-lg hover:bg-emerald-700 transition-all uppercase tracking-widest text-xs flex items-center">
            <CheckCircleIcon class="w-5 h-5 mr-3" /> {{ editingId ? 'Mettre à jour' : 'Valider le rouleau' }}
          </button>
        </div>
      </div>
    </transition>

    <!-- Content Area -->
    <div v-if="filteredCantos.length > 0">
      
      <!-- GRID VIEW -->
      <transition-group name="list" tag="div" v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-6">
        <div v-for="canto in filteredCantos" :key="canto?.id" class="bg-white p-6 rounded-[40px] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_20px_50px_rgb(0,0,0,0.08)] hover:border-emerald-200 transition-all duration-500 group relative overflow-hidden flex flex-col text-center">
          
          <!-- Actions Hover -->
          <div class="absolute top-4 right-4 flex flex-col gap-2 translate-x-12 group-hover:translate-x-0 transition-transform duration-300 z-10">
              <button @click="editCanto(canto)" class="p-2 bg-white/90 backdrop-blur shadow-sm border border-slate-100 text-slate-400 hover:text-emerald-600 rounded-xl transition-all"><Edit3Icon class="w-4 h-4" /></button>
              <button @click="confirmDeleteCanto(canto?.id)" class="p-2 bg-white/90 backdrop-blur shadow-sm border border-slate-100 text-slate-400 hover:text-red-500 rounded-xl transition-all"><Trash2Icon class="w-4 h-4" /></button>
          </div>

          <!-- Color Circle -->
          <div class="mx-auto mb-6 relative group/circle">
             <div class="w-24 h-24 rounded-full border-4 border-white shadow-xl flex items-center justify-center relative overflow-hidden transition-transform duration-500 group-hover:scale-110" :style="{ backgroundColor: getHexColor(canto.color_code) }">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]"></div>
             </div>
             <!-- Low Stock Indicator -->
             <div v-if="canto.total_length_remaining <= 10" class="absolute bottom-0 right-0 bg-red-500 text-white w-7 h-7 rounded-full flex items-center justify-center shadow-lg border-2 border-white animate-bounce">
                <AlertTriangleIcon class="w-3 h-3" />
             </div>
          </div>
          
          <h3 class="font-black text-slate-900 uppercase italic tracking-tight mb-1 truncate px-2 leading-tight">{{ canto.color_name || canto.name || 'BANDCHANT' }}</h3>
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">{{ canto.color_code }} <span class="mx-1">•</span> {{ canto.finish_type || 'STD' }}</p>

          <!-- Stock Info -->
          <div class="bg-slate-50/50 p-4 rounded-3xl border border-slate-100 mb-6 group-hover:bg-emerald-50/50 group-hover:border-emerald-100 transition-all">
             <div class="flex justify-between items-start mb-1">
                <span class="block text-[8px] font-black text-slate-300 uppercase tracking-widest">Métrage Restant</span>
                <div v-if="canto.total_length_remaining > 0" class="text-[9px] font-black text-slate-400 uppercase flex items-center">
                   <PackageIcon class="w-2.5 h-2.5 mr-1" />
                   {{ Math.floor(canto.total_length_remaining / 150) }}R + {{ (canto.total_length_remaining % 150).toFixed(1) }}m
                </div>
             </div>
             <span class="text-lg font-black" :class="canto.total_length_remaining <= 10 ? 'text-red-600' : 'text-emerald-700'">{{ canto.total_length_remaining }}<span class="text-[10px] ml-0.5">M</span></span>
             <div class="w-full bg-slate-200/50 h-1.5 rounded-full mt-2 overflow-hidden p-px">
                <div class="h-full rounded-full" :class="canto.total_length_remaining <= 10 ? 'bg-red-500' : 'bg-emerald-500'" :style="{ width: (Math.min(canto.total_length_remaining, 150)/150*100)+'%' }"></div>
             </div>
          </div>

          <!-- Price -->
          <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-center gap-1.5">
             <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ canto.base_price_sell_per_m }}</span>
             <span class="text-[10px] font-black text-slate-400 uppercase">DH/M</span>
          </div>
        </div>
      </transition-group>

      <!-- LIST VIEW -->
      <div v-else class="bg-white rounded-[40px] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-100">
                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Désignation</th>
                <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fournisseur</th>
                <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Stock</th>
                <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Prix Vente</th>
                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="canto in filteredCantos" :key="canto?.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="px-8 py-5 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-12 h-12 rounded-2xl flex-shrink-0 border-2 border-white shadow-md relative overflow-hidden mr-4" :style="{ backgroundColor: getHexColor(canto.color_code) }">
                       <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/wood-pattern.png')]"></div>
                    </div>
                    <div>
                      <div class="font-black text-slate-900 uppercase italic">{{ canto.color_name || canto.name || 'BANDCHANT' }}</div>
                      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ canto.name }} <span class="mx-1">•</span> {{ canto.color_code }} <span class="mx-1">•</span> {{ canto.finish_type || 'STD' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                   <div v-if="canto.supplier" class="flex items-center">
                      <TruckIcon class="w-3.5 h-3.5 mr-2 text-slate-300" />
                      <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">{{ canto.supplier.name }}</span>
                   </div>
                   <span v-else class="text-[10px] font-bold text-slate-300 uppercase tracking-widest italic">Stock Initial</span>
                </td>
                <td class="px-6 py-5 text-center whitespace-nowrap">
                   <div class="flex flex-col items-center">
                      <div class="inline-flex items-center px-4 py-2 rounded-2xl text-xs font-black uppercase tracking-widest shadow-sm" :class="canto.total_length_remaining <= 10 ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100'">
                         {{ canto.total_length_remaining }} Metres
                      </div>
                      <div v-if="canto.total_length_remaining > 0" class="text-[10px] font-bold text-slate-400 mt-2 flex items-center">
                         <span v-if="Math.floor(canto.total_length_remaining / 150) > 0">
                            {{ Math.floor(canto.total_length_remaining / 150) }} Rouleau(x)
                         </span>
                         <span v-if="canto.total_length_remaining % 150 > 0">
                            <span v-if="Math.floor(canto.total_length_remaining / 150) > 0" class="mx-1">+</span>
                            {{ (canto.total_length_remaining % 150).toFixed(1) }}m
                         </span>
                      </div>
                      <div v-else class="text-[10px] font-bold text-red-400 mt-2 italic flex items-center">
                         <AlertTriangleIcon class="w-3 h-3 mr-1" /> Rupture
                      </div>
                   </div>
                </td>
                <td class="px-6 py-5 text-right whitespace-nowrap font-black text-emerald-600 text-lg">
                  {{ canto.base_price_sell_per_m }} <span class="text-[10px] text-emerald-400 font-bold uppercase ml-0.5">DH/m</span>
                </td>
                <td class="px-8 py-5 text-right whitespace-nowrap">
                  <div class="flex justify-end gap-2">
                    <button @click="editCanto(canto)" class="p-2.5 text-slate-400 hover:text-emerald-600 hover:bg-white rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"><Edit3Icon class="w-4 h-4" /></button>
                    <button @click="confirmDeleteCanto(canto?.id)" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-white rounded-xl transition-all shadow-sm border border-transparent hover:border-slate-100"><Trash2Icon class="w-4 h-4" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="relative group mt-12 py-32 bg-white/40 backdrop-blur-md rounded-[64px] border-2 border-dashed border-slate-200/50 flex flex-col items-center justify-center overflow-hidden animate-in fade-in zoom-in duration-700">
       <div class="absolute -inset-10 bg-gradient-to-tr from-emerald-500/5 to-teal-500/5 rounded-full blur-3xl group-hover:opacity-100 transition duration-1000"></div>
       <div class="relative bg-white/80 p-8 rounded-[32px] shadow-soft border border-slate-50 mb-6">
          <PackageSearchIcon class="w-20 h-20 text-slate-200 group-hover:text-emerald-400 transition-colors duration-500" />
       </div>
       <div class="relative text-center max-w-sm px-6">
          <p class="font-black text-slate-900 uppercase tracking-widest text-lg mb-2">Inventaire Vide</p>
          <p class="text-slate-400 font-bold text-sm">Aucun rouleau ne correspond à votre recherche. Essayez d'ajouter un nouvel article ou d'ajuster vos filtres.</p>
       </div>
       <button @click="toggleAddForm" class="mt-8 relative px-8 py-3 bg-white text-emerald-600 rounded-2xl font-black text-sm border border-emerald-100 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all active:scale-95">
         Commencer l'Inventaire
       </button>
    </div>

    <!-- ADJUSTMENT / KOSOR MODAL -->
    <div v-if="isAdjustModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
        <div class="bg-white rounded-[40px] w-full max-w-md shadow-2xl overflow-hidden" @click.stop>
            <!-- Header -->
            <div class="bg-rose-500 p-8 flex justify-between items-start">
                <div>
                    <h3 class="text-2xl font-black text-white flex items-center">
                        <i class="fa-solid fa-triangle-exclamation mr-3 text-rose-200"></i> Déclarer une Casse
                    </h3>
                    <p class="text-sm text-rose-100 mt-1 font-medium">Retirer des articles endommagés du stock</p>
                </div>
                <button @click="closeAdjustmentModal" class="text-rose-200 hover:text-white transition-colors bg-white/10 p-2 rounded-xl">
                    <XIcon class="w-6 h-6" />
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-8 space-y-6">
                <!-- Select Article -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Article Endommagé *</label>
                    <select v-model="adjustForm.item_id" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 font-black text-slate-700 appearance-none shadow-inner">
                        <option value="">Sélectionner un rouleau...</option>
                        <option v-for="item in (cantos || [])" :key="item?.id || Math.random()" :value="item?.id">
                            {{ item ? `${item.name} - ${item.color_code} (Stock: ${item.total_length_remaining}m)` : '' }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Quantité -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Qté à retirer *</label>
                        <div class="relative group">
                            <input type="number" v-model="adjustForm.quantity" min="0.1" step="0.1" class="w-full p-4 bg-rose-50/50 border border-rose-100 text-rose-900 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 font-black text-xl shadow-inner transition-all">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 font-black text-rose-300 text-xs uppercase tracking-widest">m</span>
                        </div>
                    </div>
                    
                    <!-- Motif -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Motif *</label>
                        <select v-model="adjustForm.reason" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 font-black text-slate-700 appearance-none shadow-inner">
                            <option value="kosor">Kosor / Cassé</option>
                            <option value="chute">Chute inexploitable</option>
                            <option value="erreur">Erreur d'inventaire</option>
                        </select>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Détails / Notes</label>
                    <textarea v-model="adjustForm.notes" rows="3" placeholder="Ex: Rayure profonde sur le rouleau..." class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 text-sm font-medium text-slate-600 shadow-inner transition-all"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 p-6 border-t border-slate-100 flex justify-end space-x-4">
                <button @click="closeAdjustmentModal" class="px-6 py-4 text-sm font-black text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all">
                    Annuler
                </button>
                <button @click="submitAdjustment" :disabled="!adjustForm.item_id || adjustForm.quantity <= 0" 
                        class="px-8 py-4 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black rounded-2xl transition-all shadow-xl shadow-rose-200 flex items-center active:scale-95">
                    <CheckIcon class="w-5 h-5 mr-2" /> Valider la Casse
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <TransitionRoot appear :show="deleteModalOpen" as="template">
      <Dialog as="div" @close="deleteModalOpen = false" class="relative z-50">
        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-[32px] bg-white p-8 text-left align-middle shadow-2xl transition-all border border-slate-100">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-50 mb-6 mx-auto">
                  <AlertTriangleIcon class="w-8 h-8 text-red-500" />
                </div>
                <DialogTitle as="h3" class="text-2xl font-black text-center text-slate-900 mb-2">Supprimer ce rouleau ?</DialogTitle>
                <div class="mt-2 text-center">
                  <p class="text-sm font-medium text-slate-500">Cette action est irréversible. Toutes les données associées à ce rouleau de bandchant seront effacées.</p>
                </div>

                <div class="mt-8 flex gap-4">
                  <button type="button" class="w-full justify-center rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none transition-all" @click="deleteModalOpen = false">
                    Annuler
                  </button>
                  <button type="button" class="w-full justify-center rounded-2xl border border-transparent bg-red-600 px-4 py-4 text-sm font-black text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-100 transition-all shadow-lg shadow-red-200" @click="executeDelete">
                    Oui, supprimer
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Quick Add Supplier Modal -->
    <div v-if="showSupplierModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[60] flex items-center justify-center p-4">
      <div class="bg-white rounded-[32px] w-full max-w-md overflow-hidden shadow-2xl border border-slate-100 transform transition-all animate-fade-in-scale">
        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
          <h3 class="font-black text-xl text-slate-800 uppercase tracking-tight">Nouveau Fournisseur</h3>
          <button type="button" @click="showSupplierModal = false" class="w-10 h-10 rounded-full flex items-center justify-center text-slate-400 hover:bg-white hover:text-slate-600 transition-all">
            <Trash2Icon class="w-5 h-5 rotate-45" />
          </button>
        </div>
        <div class="p-8 space-y-6">
          <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Nom / Entreprise *</label>
            <input type="text" v-model="newSupplier.name" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-emerald-500 font-black text-slate-700" placeholder="Ex: Atlas Bois">
          </div>
          <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Téléphone</label>
            <input type="text" v-model="newSupplier.phone" class="w-full bg-slate-50 border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-emerald-500 font-black text-slate-700" placeholder="06...">
          </div>
        </div>
        <div class="p-6 border-t border-slate-50 bg-slate-50/50 flex justify-end gap-3">
          <button type="button" @click="showSupplierModal = false" class="px-6 py-3 font-bold text-slate-500 hover:text-slate-700 transition-colors uppercase text-xs tracking-widest">
            Annuler
          </button>
          <button type="button" @click="saveNewSupplier" :disabled="!newSupplier.name || isSavingSupplier" class="px-8 py-3 font-black text-white bg-emerald-600 hover:bg-emerald-700 rounded-2xl shadow-lg shadow-emerald-200 transition-all active:scale-95 disabled:opacity-50 flex items-center uppercase text-xs tracking-widest">
            <div v-if="isSavingSupplier" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></div>
            <CheckCircleIcon v-else class="w-4 h-4 mr-2" />
            Enregistrer
          </button>
        </div>
      </div>
    </div>

    <!-- IMPORT MODAL (Master Workflow) -->
    <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md animate-fade-in">
        <div class="bg-white rounded-[48px] w-full max-w-xl shadow-2xl overflow-hidden" @click.stop>
            <!-- Header -->
            <div class="bg-gradient-to-br from-indigo-600 to-brand-600 p-10 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                <div class="relative flex justify-between items-start">
                    <div>
                        <h3 class="text-3xl font-black text-white flex items-center tracking-tight">
                           <FileUpIcon class="w-8 h-8 mr-3 text-indigo-200" /> Importer Stock Initial
                        </h3>
                        <p class="text-indigo-100 mt-2 font-medium">Initialisez votre inventaire en masse via Excel.</p>
                    </div>
                    <button @click="isImportModalOpen = false" class="text-indigo-200 hover:text-white transition-colors bg-white/10 p-2 rounded-2xl backdrop-blur-md">
                        <XIcon class="w-6 h-6" />
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="p-10 space-y-10">
                <!-- Step 1: Template -->
                <div class="flex gap-6 items-start group">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex flex-shrink-0 items-center justify-center text-indigo-600 font-black text-xl shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">1</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 mb-1">Télécharger le modèle</h4>
                        <p class="text-sm text-slate-500 font-medium mb-4">Utilisez notre structure standard pour éviter toute erreur de formatage.</p>
                        <button @click="downloadStockTemplate" class="inline-flex items-center px-5 py-2.5 bg-slate-50 text-indigo-600 border border-indigo-100 rounded-xl font-black text-xs hover:bg-indigo-50 transition-all active:scale-95 shadow-sm">
                           <FileDownIcon class="w-4 h-4 mr-2" /> Télécharger le Modèle (.xlsx)
                        </button>
                    </div>
                </div>

                <!-- Step 2: Fill -->
                <div class="flex gap-6 items-start group">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex flex-shrink-0 items-center justify-center text-amber-600 font-black text-xl shadow-sm group-hover:bg-amber-600 group-hover:text-white transition-all duration-300">2</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 mb-1">Préparer vos données</h4>
                        <p class="text-sm text-slate-500 font-medium leading-relaxed">Remplissez le fichier avec vos quantités actuelles. Respectez bien les colonnes <code class="bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600 font-bold">code</code> et <code class="bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600 font-bold">quantite</code>.</p>
                    </div>
                </div>

                <!-- Step 3: Upload -->
                <div class="flex gap-6 items-start group">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex flex-shrink-0 items-center justify-center text-emerald-600 font-black text-xl shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">3</div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 mb-1">Importer le fichier</h4>
                        <p class="text-sm text-slate-500 font-medium mb-5">Une fois prêt, sélectionnez votre fichier pour injecter le stock.</p>
                        
                        <div class="relative group/input">
                           <input type="file" @change="handleImportFile" class="hidden" ref="importInputModal" accept=".xlsx,.xls,.csv">
                           <button @click="$refs.importInputModal.click()" :disabled="isLoading" 
                                   class="w-full flex flex-col items-center justify-center py-8 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50 hover:bg-white hover:border-brand-400 hover:shadow-premium transition-all group-hover/input:scale-[1.01]">
                               <div v-if="isLoading" class="flex flex-col items-center">
                                   <RotateCwIcon class="w-10 h-10 text-brand-500 animate-spin mb-2" />
                                   <span class="text-xs font-black text-brand-600 uppercase tracking-widest">Importation en cours...</span>
                               </div>
                               <div v-else class="flex flex-col items-center">
                                   <div class="w-14 h-14 bg-white rounded-2xl shadow-premium flex items-center justify-center mb-3 text-slate-400 group-hover:text-brand-500 transition-colors">
                                       <UploadCloudIcon class="w-8 h-8" />
                                   </div>
                                   <span class="text-sm font-black text-slate-800">Cliquez pour choisir un fichier</span>
                                   <span class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">Excel ou CSV supportés</span>
                               </div>
                           </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { 
  PaletteIcon, RotateCwIcon, SearchIcon, PlusCircleIcon, 
  Edit3Icon, Trash2Icon, LayoutGridIcon, ListIcon, MoreHorizontalIcon,
  PackageSearchIcon, HistoryIcon, DollarSignIcon, XIcon, CheckIcon,
  CheckCircleIcon, AlertTriangleIcon, TruckIcon, ShieldCheckIcon,
  TrendingDownIcon, TagIcon, InfoIcon, FileUpIcon, FileDownIcon,
  UploadCloudIcon, PackageIcon
} from 'lucide-vue-next';
import WoodTexturePicker from '@/Components/WoodTexturePicker.vue';
import { commonTextures } from '@/colors';

const cantos = ref([]);
const suppliers = ref([]);
const showAddForm = ref(false);
const editingId = ref(null);
const searchQuery = ref('');
const viewMode = ref('list'); // Default to list for Canto as seen in screenshot
const invoiceFile = ref(null);
const isLoading = ref(false);
const importInput = ref(null);
const importInputModal = ref(null);
const isImportModalOpen = ref(false);

// Quick Supplier Add State
const showSupplierModal = ref(false);
const isSavingSupplier = ref(false);
const newSupplier = ref({ name: '', phone: '' });

// Adjustment (Kosor) State
const isAdjustModalOpen = ref(false);
const adjustForm = ref({ item_id: '', quantity: 1, reason: 'kosor', notes: '' });

const handleFileUpload = (event) => {
  invoiceFile.value = event.target.files[0];
};

// Modal State
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

// Palette removed (moved to colors.js)

const form = ref({ 
    existing_id: null,
    name: '', color_code: '', color_name: '', finish_type: '', provider_catalog: '', 
    rolls: 1, meters_per_roll: 150, 
    unit_cost_m: 3.5, sell_price_m: 6, supplier_id: null, amount_paid: 0 
});

// Auto-calculate Total Meters
const totalMeters = computed(() => {
    return (form.value.rolls || 0) * (form.value.meters_per_roll || 0);
});

// Auto-calculate Invoice Global Cost
const globalCost = computed(() => {
    return (totalMeters.value * (form.value.unit_cost_m || 0)).toFixed(2);
});

const filteredCantos = computed(() => {
  if (!searchQuery.value) return cantos.value;
  const q = searchQuery.value.toLowerCase();
  return cantos.value.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) ||
    (c.color_name && c.color_name.toLowerCase().includes(q)) ||
    c.color_code.toLowerCase().includes(q) || 
    (c.finish_type && c.finish_type.toLowerCase().includes(q)) ||
    (c.provider_catalog && c.provider_catalog.toLowerCase().includes(q)) ||
    (c.supplier && c.supplier.name.toLowerCase().includes(q))
  );
});

const loadCantos = async () => { 
  isLoading.value = true;
  try { 
    const res = await axios.get('/api/admin/cantos'); 
    cantos.value = res.data; 
  } catch(e) { 
    console.error(e); 
  } finally {
    isLoading.value = false;
  }
};
const loadSuppliers = async () => { try { const res = await axios.get('/api/admin/suppliers'); suppliers.value = res.data; } catch(e) { console.error(e); } };

const saveNewSupplier = async () => {
  if (!newSupplier.value.name) return;
  isSavingSupplier.value = true;
  try {
    const res = await axios.post('/api/admin/suppliers', newSupplier.value);
    suppliers.value.push(res.data);
    form.value.supplier_id = res.data.id;
    showSupplierModal.value = false;
    newSupplier.value = { name: '', phone: '' };
  } catch (e) {
    alert('Erreur lors de la création du fournisseur');
  } finally {
    isSavingSupplier.value = false;
  }
};

const toggleAddForm = () => { if (showAddForm.value && editingId.value) { editingId.value = null; resetForm(); } showAddForm.value = !showAddForm.value; };
const resetForm = () => { form.value = { existing_id: null, name: '', color_code: '', color_name: '', finish_type: '', provider_catalog: '', rolls: 1, meters_per_roll: 150, unit_cost_m: 3.5, sell_price_m: 6, supplier_id: null, amount_paid: 0 }; invoiceFile.value = null; };
const handleExistingSelection = (event) => {
  const id = event.target.value;
  if (!id) {
    resetForm();
    return;
  }
  const c = cantos.value.find(item => item?.id == id);
  if (c) {
    form.value.existing_id = c.id;
    form.value.name = c.name;
    form.value.color_code = c.color_code;
    form.value.color_name = c.color_name;
    form.value.finish_type = c.finish_type;
    form.value.provider_catalog = c.provider_catalog;
    form.value.sell_price_m = c.base_price_sell_per_m;
    form.value.unit_cost_m = c.cost_price_per_m;
    form.value.meters_per_roll = 150; // Default reset for new entry logic
    form.value.rolls = 1;
    form.value.supplier_id = c.supplier_id;
  }
};
const selectColor = (color) => { form.value.color_code = color.code; if(!form.value.name) form.value.name = 'BANDCHANT ' + color.name.toUpperCase(); };

const saveCanto = async () => {
  const calculatedTotal = parseFloat(globalCost.value);

  if (!form.value.supplier_id || calculatedTotal <= 0) {
    return alert('Fournisseur et Prix Achat sont obligatoires !');
  }

  try {
    const costPerM = form.value.unit_cost_m;
    
    if (editingId.value) {
      // Edit mode
      const payload = { 
          name: form.value.name, 
          color_code: form.value.color_code, 
          color_name: form.value.color_name, 
          finish_type: form.value.finish_type, 
          provider_catalog: form.value.provider_catalog, 
          total_length_remaining: totalMeters.value, 
          cost_price_per_m: costPerM, 
          base_price_sell_per_m: form.value.sell_price_m, 
          width_mm: 22, 
          thickness_mm: 0.8,
          supplier_id: form.value.supplier_id
      };
      await axios.put(`/api/admin/cantos/${editingId.value}`, payload);
    } else {
      // New Entry: Use Purchase API with FormData
      const payloadItems = [
        {
          category: 'canto',
          total_cost: calculatedTotal,
          data: {
            existing_id: form.value.existing_id,
            name: form.value.name,
            color_code: form.value.color_code,
            color_name: form.value.color_name,
            finish_type: form.value.finish_type,
            provider_catalog: form.value.provider_catalog,
            width_mm: 22,
            thickness_mm: 0.8,
            total_length_remaining: totalMeters.value,
            cost_price_per_m: costPerM,
            base_price_sell_per_m: form.value.sell_price_m
          }
        }
      ];

      const formData = new FormData();
      formData.append('supplier_id', form.value.supplier_id);
      formData.append('reference_invoice', 'ENTRÉE-STOCK-RAPIDE-CANTO-' + Date.now());
      formData.append('total_amount', calculatedTotal);
      formData.append('amount_paid', form.value.amount_paid || 0);
      formData.append('payment_method', 'cash');
      formData.append('items', JSON.stringify(payloadItems));
      
      if (invoiceFile.value) {
        formData.append('invoice_document', invoiceFile.value);
      }

      await axios.post('/api/admin/purchases', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('Stock enregistré ET Facture Fournisseur générée !');
    }
    
    showAddForm.value = false; resetForm(); editingId.value = null; loadCantos();
  } catch(e) { 
    console.error(e);
    alert('Erreur lors de l\'enregistrement'); 
  }
};

const editCanto = (canto) => {
  editingId.value = canto.id;
  form.value = { 
      existing_id: canto.id,
      name: canto.name, 
      color_code: canto.color_code, 
      color_name: canto.color_name,
      finish_type: canto.finish_type, 
      provider_catalog: canto.provider_catalog, 
      rolls: 1,
      meters_per_roll: canto.total_length_remaining, 
      unit_cost_m: canto.cost_price_per_m, 
      amount_paid: 0,
      sell_price_m: canto.base_price_sell_per_m,
      supplier_id: canto.supplier_id
  };
  showAddForm.value = true;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const confirmDeleteCanto = (id) => {
  itemToDelete.value = id;
  deleteModalOpen.value = true;
};

const executeDelete = async () => {
  if (!itemToDelete.value) return;
  try { 
    await axios.delete(`/api/admin/cantos/${itemToDelete.value}`); 
    loadCantos(); 
    deleteModalOpen.value = false;
    itemToDelete.value = null;
  } catch(e) { alert('Erreur'); }
};

const getHexColor = (code) => {
  if (!code) return '#f1f5f9';
  const c = code.toUpperCase();
  const texture = commonTextures.find(t => t.code.toUpperCase() === c);
  if (texture && texture.hex) return texture.hex;
  
  if (c.includes('BLANC')) return '#ffffff';
  if (c.includes('CHENE') || c.includes('TERRA')) return '#b45309';
  if (c.includes('NOIR')) return '#1e293b';
  
  let hash = 0;
  for (let i = 0; i < c.length; i++) { hash = c.charCodeAt(i) + ((hash << 5) - hash); }
  let color = '#';
  for (let i = 0; i < 3; i++) {
    let value = (hash >> (i * 8)) & 0xFF;
    color += ('00' + value.toString(16)).substr(-2);
  }
  return color;
};

// Adjustment (Kosor) Methods
const openAdjustmentModal = () => {
    isAdjustModalOpen.value = true;
};
const closeAdjustmentModal = () => {
    isAdjustModalOpen.value = false;
    adjustForm.value = { item_id: '', quantity: 1, reason: 'kosor', notes: '' };
};
const submitAdjustment = async () => {
    if (!adjustForm.value.item_id || adjustForm.value.quantity <= 0) return;
    try {
        await axios.post('/api/admin/inventory/adjust', {
            item_id: adjustForm.value.item_id,
            item_type: 'StockCanto',
            quantity: adjustForm.value.quantity,
            reason: adjustForm.value.reason,
            notes: adjustForm.value.notes
        });
        alert('Stock ajusté avec succès !');
        closeAdjustmentModal();
        loadCantos();
    } catch (e) {
        console.error(e);
        alert('Erreur lors de l\'ajustement du stock.');
    }
};

const exportData = async (type) => {
    try {
        const res = await axios.post(`/api/export/${type}`, {}, { responseType: 'blob' });
        
        let fileName = `${type}_export_${new Date().getTime()}.xlsx`;
        const disposition = res.headers['content-disposition'] || res.headers['Content-Disposition'];
        
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) { 
                fileName = matches[1].replace(/['"]/g, '');
            }
        }

        // Force extension if missing
        if (!fileName.toLowerCase().endsWith('.xlsx')) {
            fileName += '.xlsx';
        }
        
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        
        setTimeout(() => {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }, 200);
    } catch (e) {
        console.error('Export Error:', e);
        alert('Erreur lors du téléchargement de l\'export');
    }
};

const triggerImport = () => {
    isImportModalOpen.value = true;
};

const downloadStockTemplate = async () => {
    try {
        const res = await axios.get('/api/admin/stock/import-template', { responseType: 'blob' });
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'modele_stock_initial.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error(e);
        alert('Erreur lors du téléchargement du modèle.');
    }
};

const handleImportFile = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    isLoading.value = true;
    try {
        const res = await axios.post('/api/admin/stock/import-initial/canto', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (res.data.success) {
            alert(res.data.message || 'Stock initial importé avec succès !');
            isImportModalOpen.value = false;
            loadCantos();
        } else {
            alert(res.data.message || 'Erreur lors de l\'importation');
        }
    } catch (e) {
        console.error(e);
        alert('Erreur lors de l\'importation : ' + (e.response?.data?.message || e.message));
    } finally {
        isLoading.value = false;
        if (event.target) event.target.value = ''; // Reset input
    }
};

onMounted(() => {
    loadCantos();
    loadSuppliers();
});
</script>

<style scoped>
.shadow-soft { box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-20px); }
</style>
