const express = require('express');
const path = require('path');
const PesaPal = require('pesapal'); // Import the PesaPal library

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware to parse JSON and URL-encoded data
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from the "public" directory
app.use(express.static(path.join(__dirname, 'public')));

// PesaPal configuration
const pesapal = new PesaPal({
  consumerKey: 'dJx8ofTbwuSs3rPH0m8s7c142c1mVZht', // Replace with your PesaPal consumer key
  consumerSecret: 'PVjWH6PhjIVrz0+Zhcqtxnnp9NU=', // Replace with your PesaPal consumer secret
});

// Route to initialize payment
app.post('/api/initiate-payment', async (req, res) => {
  const { firstName, lastName, email, phone, amount, description } = req.body;

  try {
    const paymentDetails = {
      amount,
      description,
      type: 'MERCHANT',
      reference: `REF-${Date.now()}`,
      firstName,
      lastName,
      email,
      phoneNumber: phone,
      currency: 'USD',
    };

    const paymentUrl = await pesapal.initiatePayment(paymentDetails);
    res.json({ success: true, paymentUrl });
  } catch (error) {
    console.error('Error initiating payment:', error);
    res.status(500).json({ success: false, message: 'Failed to initiate payment' });
  }
});

// Route to handle payment callback
app.get('/api/payment-callback', async (req, res) => {
  const { transactionId, reference } = req.query;

  try {
    const paymentStatus = await pesapal.getPaymentStatus(transactionId, reference);
    res.json({ success: true, paymentStatus });
  } catch (error) {
    console.error('Error fetching payment status:', error);
    res.status(500).json({ success: false, message: 'Failed to fetch payment status' });
  }
});

// Fallback to index.html for SPA routing
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
