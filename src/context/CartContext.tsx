import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { CartItem, Product } from '../types';

interface CartContextType {
  cartItems: CartItem[];
  isCartOpen: boolean;
  activeView: string;
  setActiveView: (view: string) => void;
  addToCart: (product: Product, selectedAttributes?: any) => void;
  removeFromCart: (productId: string, selectedAttributes?: any) => void;
  updateQuantity: (productId: string, selectedAttributes: any, quantity: number) => void;
  getCartTotal: () => number;
  getCartItemCount: () => number;
  clearCart: () => void;
  openCart: () => void;
  closeCart: () => void;
  goToCheckout: () => void;
  goToCart: () => void;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [activeView, setActiveView] = useState('product');

  const addToCart = useCallback((product: Product, selectedAttributes: any = {}) => {
    console.log('Adding to cart:', product.name, selectedAttributes);
    setCartItems(prevItems => {
      const existingItem = prevItems.find(item => 
        item.id === product.id && 
        JSON.stringify(item.selectedAttributes) === JSON.stringify(selectedAttributes)
      );

      if (existingItem) {
        return prevItems.map(item =>
          item.id === product.id && 
          JSON.stringify(item.selectedAttributes) === JSON.stringify(selectedAttributes)
            ? { ...item, quantity: item.quantity + 1 }
            : item
        );
      }

      return [...prevItems, { 
        ...product, 
        quantity: 1, 
        selectedAttributes 
      }];
    });
    console.log('Opening cart sidebar');
    setIsCartOpen(true);
  }, []);

  const removeFromCart = useCallback((productId: string, selectedAttributes: any = {}) => {
    setCartItems(prevItems => 
      prevItems.filter(item => 
        !(item.id === productId && 
          JSON.stringify(item.selectedAttributes) === JSON.stringify(selectedAttributes))
      )
    );
  }, []);

  const updateQuantity = useCallback((productId: string, selectedAttributes: any = {}, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(productId, selectedAttributes);
      return;
    }

    setCartItems(prevItems =>
      prevItems.map(item =>
        item.id === productId && 
        JSON.stringify(item.selectedAttributes) === JSON.stringify(selectedAttributes)
          ? { ...item, quantity }
          : item
      )
    );
  }, [removeFromCart]);

  const getCartTotal = useCallback(() => {
    return cartItems.reduce((total, item) => total + (item.basePrice * item.quantity), 0);
  }, [cartItems]);

  const getCartItemCount = useCallback(() => {
    return cartItems.reduce((count, item) => count + item.quantity, 0);
  }, [cartItems]);

  const clearCart = useCallback(() => {
    setCartItems([]);
  }, []);

  const openCart = useCallback(() => {
    console.log('Opening cart manually');
    setIsCartOpen(true);
  }, []);
  
  const closeCart = useCallback(() => {
    console.log('Closing cart');
    setIsCartOpen(false);
  }, []);

  const goToCheckout = useCallback(() => {
    console.log('Going to checkout');
    setActiveView('checkout');
    setIsCartOpen(false);
  }, []);

  const goToCart = useCallback(() => {
    console.log('Going to cart view');
    setActiveView('checkout');
    setIsCartOpen(false);
  }, []);
  const value = {
    cartItems,
    isCartOpen,
    activeView,
    setActiveView,
    addToCart,
    removeFromCart,
    updateQuantity,
    getCartTotal,
    getCartItemCount,
    clearCart,
    openCart,
    closeCart,
    goToCheckout,
    goToCart
  };

  return (
    <CartContext.Provider value={value}>
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => {
  const context = useContext(CartContext);
  if (context === undefined) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};