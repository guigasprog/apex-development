import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  // BehaviorSubject para emitir o estado de login (começa como deslogado)
  private loggedInStatus = new BehaviorSubject<boolean>(false);
  public isLoggedIn$ = this.loggedInStatus.asObservable();

  // Mock de dados do usuário (se logado)
  private currentUser = new BehaviorSubject<{ name: string; email: string } | null>(null);
  public currentUser$ = this.currentUser.asObservable();

  constructor() { }

  // Simula o login
  login() {
    this.loggedInStatus.next(true);
    this.currentUser.next({ name: 'Usuário Teste', email: 'teste@exemplo.com' });
    console.log('AuthService: Logged In');
  }

  // Simula o logout
  logout() {
    this.loggedInStatus.next(false);
    this.currentUser.next(null);
    console.log('AuthService: Logged Out');
  }

  // Método para alternar o estado (apenas para teste fácil)
  toggleLogin() {
    if (this.loggedInStatus.value) {
      this.logout();
    } else {
      this.login();
    }
  }
}
