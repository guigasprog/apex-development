import db from '../database/connection.js';

// Middleware para identificar o tenant (loja) com base no subdomínio
export async function identifyTenant(req, res, next) {
    // A origem nos diz quem está fazendo a chamada (ex: http://loja-exemplo.apex-store.com)
    const origin = req.get('origin');
    
    if (!origin) {
        return next(); // Se não houver origem, continua sem identificar
    }

    try {
        // Extrai o host (loja-exemplo.apex-store.com)
        const host = new URL(origin).hostname;
        const slug = host.split('.')[0];

        // Se o slug for 'www' ou o domínio principal, ignora
        if (slug === 'www' || slug === 'apex-store') {
            return next();
        }

        // Faz a busca no banco de dados usando Promises para um código mais limpo
        const tenant = await new Promise((resolve, reject) => {
            const query = "SELECT * FROM tenants WHERE slug = ?";
            db.get(query, [slug], (err, row) => {
                if (err) reject(err);
                resolve(row);
            });
        });

        if (tenant) {
            // Anexa os dados do tenant à requisição para serem usados nas rotas
            req.tenant = tenant;
        }

        next(); // Passa para a próxima etapa (a rota da API)

    } catch (error) {
        console.error("Erro ao identificar o tenant:", error);
        next(); // Continua mesmo se houver erro, para não travar a aplicação
    }
}