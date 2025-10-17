import sqlite3 from 'sqlite3';
import dotenv from 'dotenv';

// Carrega as variáveis do arquivo .env
dotenv.config();

const dbPath = process.env.DATABASE_PATH;

if (!dbPath) {
    throw new Error("Caminho do banco de dados (DATABASE_PATH) não definido no arquivo .env");
}

// Cria a conexão com o banco de dados em modo de apenas leitura para segurança
const db = new sqlite3.Database(dbPath, sqlite3.OPEN_READONLY, (err) => {
    if (err) {
        console.error('Erro ao conectar ao banco de dados SQLite:', err.message);
    } else {
        console.log('Conectado com sucesso ao banco de dados permission.db.');
    }
});

export default db;