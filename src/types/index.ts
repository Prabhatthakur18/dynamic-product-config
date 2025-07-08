export interface Product {
  id: string;
  name: string;
  image: string;
  basePrice: number;
  originalPrice?: number;
  category: string;
  attributeTypes: string[]; // e.g., ['brand', 'model'] or ['brand', 'color']
  availableAttributes: ProductAttributeSet;
  isRecommended?: boolean;
  isComplementary?: boolean;
  isSpecialOffer?: boolean;
  stockStatus?: string;
}

export interface CartItem extends Product {
  quantity: number;
  selectedAttributes: {
    [key: string]: string; // Dynamic attributes
  };
}

export interface AttributeOption {
  value: string;
  label: string;
  priceModifier?: number;
}

export interface ProductAttributeSet {
  [attributeType: string]: AttributeOption[]; // Dynamic attribute types
}

export interface CSVProductData {
  products: Product[];
  complementaryProducts: { [productId: string]: Product[] };
  recommendedProducts: Product[];
}

export interface CSVAttributeData {
  productId: string;
  attributeType: string;
  attributeValue: string;
  attributeLabel: string;
  priceModifier: number;
}

export interface BulkPurchaseRequest {
  productId: string;
  quantity: number;
  attributes: { [key: string]: string }; // Dynamic attributes
  contactInfo: {
    name: string;
    email: string;
    phone: string;
    company?: string;
    message?: string;
  };
}