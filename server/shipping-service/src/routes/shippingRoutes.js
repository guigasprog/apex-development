const express = require("express");
const router = express.Router();
const shippingController = require("../controllers/shippingController");

// A rota continua a mesma, mas a lógica por trás dela foi simplificada.
router.post("/calculate", shippingController.calculate);

module.exports = router;
