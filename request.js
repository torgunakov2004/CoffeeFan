
const express = require('express');
const app = express();
const axios = require('axios');

app.post('/verify-recaptcha', (req, res) => {
  const token = req.body.token;
  const secretKey = 'torgunakov-1719049358583'; 
  const siteKey = 'Y6Lelyf4pAAAAAB1phcYIU9zSQk1u_G4fy4-lldMx'; 

  const requestBody = {
    secret: secretKey,
    response: token,
    remoteip: req.ip,
  };

  axios.post(`https://www.google.com/recaptcha/api/siteverify`, requestBody)
    .then(response => {
      const verificationResponse = response.data;
      if (verificationResponse.success) {
        res.json({ success: true });
      } else {
        res.status(403).json({ success: false });
      }
    })
    .catch(error => {
      console.error(error);
      res.status(500).json({ success: false });
    });
});