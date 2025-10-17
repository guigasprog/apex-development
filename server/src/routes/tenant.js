import { Router } from 'express';
import db from '../database/connection.js';

const router = Router();

// Rota: GET /api/tenant/details
// Retorna os detalhes da loja e seu tema com base no subdomínio identificado
router.get('/details', async (req, res) => {
    // O middleware 'identifyTenant' já deve ter anexado os dados do tenant
    if (!req.tenant) {
        return res.status(404).json({ error: 'Loja não identificada. Verifique o subdomínio.' });
    }

    try {
        // Busca o tema associado ao tenant_id
        const theme = await new Promise((resolve, reject) => {
            const query = "SELECT * FROM tenant_themes WHERE tenant_id = ?";
            db.get(query, [req.tenant.id], (err, row) => {
                if (err) reject(err);
                resolve(row);
            });
        });

        if (!theme) {
            return res.status(404).json({ error: 'Tema da loja não encontrado.' });
        }

        // Combina as informações do tenant (logo, nome) com as do tema
        const responseData = {
            nome_loja: req.tenant.nome_loja,
            url_logo: req.tenant.url_logo,
            ...theme
        };

        res.json(responseData);

    } catch (error) {
        console.error("Erro ao buscar detalhes do tema:", error);
        res.status(500).json({ error: 'Erro interno ao buscar informações da loja.' });
    }
});

// Você pode adicionar mais rotas aqui, como para buscar produtos
// router.get('/products', ...);

export default router;