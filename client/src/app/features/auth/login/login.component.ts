import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { login } from '../../../core/state/auth.store'; // Importa a função 'login'
import { MessageService } from 'primeng/api';
import { ToastModule } from 'primeng/toast';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, ToastModule],
  providers: [MessageService],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private apiService = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private messageService = inject(MessageService);

  loginForm: FormGroup;

  constructor() {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required]],
    });
  }

  onSubmit() {
    if (this.loginForm.invalid) return;

    this.apiService.auth
      .login(this.loginForm.value)
      .then((response) => {
        login(response.data.cliente, response.data.token);

        const redirectUrl =
          this.route.snapshot.queryParams['redirectUrl'] || '/';
        this.router.navigateByUrl(redirectUrl);
      })
      .catch((error) => {
        console.error('Falha no login:', error.response?.data || error.message);
        this.messageService.add({
          severity: 'error',
          summary: 'Falha no Login',
          detail: 'Credenciais inválidas. Tente novamente.',
        });
      });
  }
}
