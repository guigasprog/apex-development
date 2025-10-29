import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Product } from '../models/product.model'; // Importa a interface
import { environment } from '../../environments/environment'; // Para a URL da API

export interface ProductInteraction {
  tipo: 'search' | 'view';
  texto_busca?: string | null;
  produto_id?: number | null;
}

export interface AutocompleteItem {
  id: number;
  name: string;
}

export interface ProductDetail extends Product {
  images: { image_url: string, descricao: string }[];
  specifications: { nome: string, valor: string }[];
}

@Injectable({
  providedIn: 'root'
})
export class ProductService {

  // Usa a mesma URL base do TenantService
  private apiUrl = environment.productsApi;

  constructor(private http: HttpClient) { }

  /**
   * Busca a lista de produtos da loja atual (identificada pela API via subdomínio).
   */
  getProducts(): Observable<Product[]> {
    // A API /api/tenant/products já sabe qual tenant buscar pelo middleware
    const url = `${this.apiUrl}`;
    return this.http.get<Product[]>(url);
  }

  /**
   * Busca os detalhes completos de um único produto.
   */
  getProductById(id: number): Observable<ProductDetail> {
    return this.http.get<ProductDetail>(`${this.apiUrl}/${id}`);
  }

  getAutocomplete(term: string): Observable<AutocompleteItem[]> {
    // Usa HttpParams para adicionar o parâmetro ?term=... de forma segura
    const params = new HttpParams().set('term', term);
    return this.http.get<AutocompleteItem[]>(`${this.apiUrl}/autocomplete`, { params });
  }

  /**
   * Busca produtos que correspondem ao termo de pesquisa.
   * @param query O termo de busca completo.
   */
  searchProducts(query: string): Observable<Product[]> {
    const url = `${this.apiUrl}/search`;
    const params = new HttpParams().set('q', query);
    return this.http.get<Product[]>(url, { params });
  }

  /**
   * Registra uma interação de usuário (busca ou view) no backend.
   * Esta é uma chamada "fire-and-forget", não esperamos dados de volta.
   */
  logInteraction(interactionData: ProductInteraction): Observable<any> {
    return this.http.post(`${this.apiUrl}/interactions`, interactionData);
  }

  // Futuramente: adicionar métodos como getProductById(id), etc.
}
