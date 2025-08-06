const express = require("express");
const router = express.Router();
const productController = require("../controllers/productController");
const authMiddleware = require("../middleware/authMiddleware");

router.post("/:id/track-view", productController.trackProductView);

router.get("/", productController.getAllProducts);
router.get("/:id", productController.getProductById);
router.get("/search/suggestions", productController.getSearchSuggestions);

module.exports = router;
