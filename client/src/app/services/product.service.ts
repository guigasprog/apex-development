import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of, switchMap, timer } from 'rxjs';
import { Product } from '../models/product.model'; // Importa a interface
import { environment } from '../../environments/environment'; // Para a URL da API

interface AutocompleteItem {
  id: number;
  name: string;
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


  getAutocomplete(term: string): Observable<AutocompleteItem[]> {
    const url = `${this.apiUrl}/autocomplete`;
    // Usa HttpParams para adicionar o parâmetro ?term=... de forma segura
    const params = new HttpParams().set('term', term);
    return this.http.get<AutocompleteItem[]>(url, { params });
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

  // Futuramente: adicionar métodos como getProductById(id), etc.
}
