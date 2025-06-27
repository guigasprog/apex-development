import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class CartService {
  private sidebarVisible = new BehaviorSubject<boolean>(false);
  sidebarVisible$ = this.sidebarVisible.asObservable();

  openCart() {
    this.sidebarVisible.next(true);
  }

  closeCart() {
    this.sidebarVisible.next(false);
  }
}
