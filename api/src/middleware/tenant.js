// src/middleware/tenant.js
import { connectPermissionDb, closeDbConnection } from '../database/connection.js';

export async function identifyTenant(req, res, next) {
    const origin = req.get('origin');
    if (!origin) return next();

    let permDb = null; // Guarda a conexão
    try {
        const host = new URL(origin).hostname;
        const slug = host.split('.')[0];

        if (slug === 'www' || slug === 'api' || slug === 'vibevault') { // Ignora subdomínios não-tenant
            return next();
        }

        permDb = await connectPermissionDb(); // Conecta ao banco principal

        const tenant = await new Promise((resolve, reject) => {
            const query = "SELECT id, nome_loja, slug, url_logo FROM tenants WHERE slug = ?";
            permDb.get(query, [slug], (err, row) => {
                if (err) reject(err);
                resolve(row);
            });
        });

        if (tenant) {
            req.tenant = tenant; // Anexa os dados do tenant
        } else {
            console.warn(`Tenant não encontrado para o slug: ${slug}`);
        }

        next();

    } catch (error) {
        console.error("Erro ao identificar o tenant:", error);
        next(error); // Passa o erro para o Express
    } finally {
        // Garante que a conexão com permission.db seja fechada
        closeDbConnection(permDb, 'permission.db');
    }
}