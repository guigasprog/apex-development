import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { identifyTenant } from './src/middleware/tenant.js';
import tenantRoutes from './src/routes/tenant.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

const corsOptions = {
  origin: function (origin, callback) {
    if (!origin) {
        return callback(null, true);
    }

    try {
        const hostname = new URL(origin).hostname;

        if (hostname.endsWith('.apex.com')) {
            callback(null, true);
        } else {
            callback(new Error('Acesso negado pela política de CORS')); // Barra a entrada
        }
    } catch (e) {
        callback(new Error('Origem inválida'));
    }
  }
};
app.use(cors(corsOptions));

// --- Middlewares ---
app.use(express.json()); // Para parsear corpos de requisição JSON
app.use(identifyTenant); // Roda o identificador de loja em TODAS as requisições

// --- Rotas da API ---
app.use('/api/tenant', tenantRoutes);

// Rota de teste
app.get('/', (req, res) => {
    res.send('API da Apex Store está no ar!');
});

// --- Inicia o Servidor ---
app.listen(PORT, () => {
    console.log(`🚀 Servidor Express rodando na porta ${PORT}`);
});