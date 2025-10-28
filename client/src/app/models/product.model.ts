
export interface Product {
  id: number;
  name: string;
  description?: string;
  price: number;
  main_image_url?: string;
}

export interface ProductImage {
    image_url: string;
    descricao?: string;
}
