import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

export const useCartStore = defineStore('cart', () => {
    const cart = ref([]);
    const selectedClient = ref('');
    const amountPaid = ref(null);
    const services = ref([]);
    const panels = ref([]);
    const cantos = ref([]);

    const cartTotal = computed(() => cart.value.reduce((t, i) => t + (i.quantity * i.unit_price), 0));
    
    const remainingCredit = computed(() => {
        let diff = cartTotal.value - (amountPaid.value || 0);
        return diff > 0 ? diff : 0;
    });

    const poseService = computed(() => services.value.find(s => s.name.toLowerCase().includes('pose')));

    const addToCart = async (product, type, requestedQty = 1) => {
        if (type === 'service' && product.name.toLowerCase().includes('pose')) {
            alert("Astuce: Pour ajouter une Pose Canto, ajoutez d'abord le Bandchant depuis la liste en bas, puis cochez 'Inclure la Pose' dans le panier à droite.");
            return;
        }

        let remainingToFulfill = requestedQty;
        let availableBatches = [];

        if (type === 'panel') {
            try {
                const res = await axios.get(`/api/products/${product.id}/batches`);
                availableBatches = res.data;
            } catch (e) {
                console.error('Erreur chargement lots', e);
            }
        }

        if (!availableBatches || availableBatches.length === 0) {
            const fallbackPrice = type === 'canto' ? Number(product.base_price_sell_per_m) : Number(product.base_price_sell || product.unit_price);
            availableBatches = [{
                id: 'default',
                price: fallbackPrice,
                available: 9999
            }];
        }

        for (const batch of availableBatches) {
            if (remainingToFulfill <= 0) break;
            
            const batchPrice = batch.price ? Number(batch.price) : Number(product.base_price_sell || product.unit_price);
            
            const alreadyInCart = cart.value
                .filter(i => i.id === product.id && i.type === type && parseFloat(i.unit_price) === parseFloat(batchPrice))
                .reduce((sum, i) => sum + Number(i.quantity), 0);
            
            const batchAvailableNow = (batch.available || 9999) - alreadyInCart;
            if (batchAvailableNow <= 0) continue;

            const qtyToTake = Math.min(remainingToFulfill, batchAvailableNow);
            
            const existing = cart.value.find(i => i.id === product.id && i.type === type && parseFloat(i.unit_price) === parseFloat(batchPrice));
            
            if (existing) {
                existing.quantity = Number(existing.quantity) + qtyToTake;
            } else {
                let name = '';
                if(type === 'panel') name = `${product.type} ${product.size_x}x${product.size_y}`;
                else if(type === 'canto') {
                    const finish = product.finish_type || 'STD';
                    const colorTitle = product.color_name || product.color_code;
                    name = `${product.name || 'BANDCHANT'} ${colorTitle} [${finish}]`;
                }
                else if(type === 'service') name = product.name;
                else if(type === 'custom_labor') name = product.name || product.type;
                else name = product.name || 'Article Générique';

                cart.value.push({
                    type, id: product.id, batch_id: batch.id, name,
                    unit_price: batchPrice,
                    quantity: qtyToTake,
                    with_pose: false,
                    custom_pose_price: 0,
                    with_canto_service: false,
                    custom_canto_service_price: 0,
                    base_canto_price: type === 'canto' ? batchPrice : null,
                    base_name: name
                });
            }
            remainingToFulfill -= qtyToTake;
        }

        if (remainingToFulfill > 0 && type === 'panel') {
            alert(`Stock insuffisant ! Il manque ${remainingToFulfill} pièces f l-inventaire.`);
        }
    };

    const removeFromCart = (index) => cart.value.splice(index, 1);

    const handleQuantityChange = async (item, newQty) => {
        const val = parseFloat(newQty);
        if (isNaN(val) || val <= 0) return removeFromCart(cart.value.indexOf(item));

        if (item.type !== 'panel') {
            item.quantity = val;
            return;
        }

        let originalProduct = panels.value.find(p => p.id === item.id);

        const productToUse = originalProduct || { 
            id: item.id, 
            category: item.type, 
            type: item.base_name || item.name, 
            base_price_sell: item.unit_price 
        };
        
        const index = cart.value.indexOf(item);
        cart.value.splice(index, 1);

        await addToCart(productToUse, item.type, val);
    };

    const addCustomLabor = (serviceName, defaultPrice, unit) => {
        const tempId = 'custom_labor_' + Date.now();
        cart.value.push({
            type: 'custom_labor',
            id: tempId,
            name: serviceName,
            unit_price: defaultPrice,
            quantity: 1,
            unit: unit
        });
    };

    const updateCantoPrices = (item) => {
        if (item.type !== 'canto') return;

        // Self-heal legacy items from sessionStorage
        if (item.base_canto_price === undefined || item.base_canto_price === null) {
            item.base_canto_price = Number(item.unit_price);
        }
        if (!item.base_name) {
            item.base_name = item.name.replace(/\[.*?\]\s*/g, '');
        }

        if (item.with_canto_service && !item.custom_canto_service_price) {
            item.custom_canto_service_price = 2.00;
        }

        recalculateCantoPriceAndName(item);
    };

    const recalculateCantoPriceAndName = (item) => {
        if (item.type !== 'canto') return;
        
        let price = Number(item.base_canto_price);
        let prefixes = [];
        
        if (item.with_canto_service) {
            price += Number(item.custom_canto_service_price || 0);
            prefixes.push('Collage');
        }
        
        item.unit_price = price;
        
        if (prefixes.length > 0) {
            item.name = `[${prefixes.join(' + ')}] ${item.base_name}`;
        } else {
            item.name = item.base_name;
        }
    };

    const clearCart = () => {
        cart.value = [];
        selectedClient.value = '';
        amountPaid.value = null;
    };

    return {
        cart, selectedClient, amountPaid, services, panels, cantos,
        cartTotal, remainingCredit, poseService,
        addToCart, removeFromCart, handleQuantityChange, addCustomLabor, 
        updateCantoPrices, clearCart
    };
}, {
    persist: {
        storage: sessionStorage,
        key: () => {
            const user = window.authUser;
            return `cart_tenant_${user?.tenant_id}_user_${user?.id}`;
        }
    }
});
