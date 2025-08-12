import express from "express";
import cors from "cors";
import fs from "fs";
import path from "path";

const port = process.env.PORT || 3000;
const app = express();

app.use(cors());
app.use(express.json());

app.get("/api", (req, res) => {
  res.send("Hello!");
});

app.get("/api/search", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/search-auto-complete.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/cart/add-to-cart", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/add-to-cart.json');
  const data = JSON.parse(rawData);
  res.send(data);
});

app.post("/api/cart/remove-from-cart", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/remove-from-cart.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/cart/remove-from-cart-and-add-to-wishlist", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/remove-from-cart-and-add-to-wishlist.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/cart/update-qty", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/update-qty.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/cart/add-coupon-code", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/add-coupon-code.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/cart/remove-coupon-code", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/remove-coupon-code.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/product/toggle-product-wishlist", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/toggle-product-wishlist.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/user/newsletter-subscribe", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/newsletter-subscribe.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/product-list/sale", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/product-list-page.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/product-view/get-shipping-fee", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/get-shipping-fee.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.get("/api/product-view/get-rating", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/get-rating.json');
  const data = JSON.parse(rawData)
  data.currentPageNumber = req.query.page
  res.send(data);
});

app.post("/api/user/make-default-address", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/make-default-address.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/user/add-edit-address", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/add-edit-address.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

app.post("/api/user/remove-address", (req, res) => {
  const rawData = fs.readFileSync('./dev/local-data/server/jsons/delete-address.json');
  const data = JSON.parse(rawData)
  res.send(data);
});

// Start the server
app.listen(port, () => {
  console.log(`Server running on port ${port}`);
  console.log(`Server running on http://localhost:${port}/api`);
});

// module.exports = app;
