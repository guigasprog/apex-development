require('dotenv').config();
const express = require('express');
const cors = require('cors');
const sequelize = require('./config/database');
const authRoutes = require('./routes/authRoutes');

require('./models/Endereco');
require('./models/Cliente');


const app = express();
app.use(cors());
app.use(express.json());

sequelize.authenticate()
  .then(() => console.log('Auth-service conectado ao MySQL.'))
  .catch(err => console.error('Não foi possível conectar ao banco de dados:', err));

app.use('/api/auth', authRoutes);

const PORT = process.env.PORT || 4001;
app.listen(PORT, () => {
  console.log(`Serviço de Autenticação rodando na porta ${PORT}`);
});