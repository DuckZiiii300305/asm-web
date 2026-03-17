const express = require('express');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.static(path.join(__dirname, 'public')));
app.use(express.json());

// Optional API proxy for same-origin deployment
app.use('/api', (req, res) => {
  const url = process.env.API_URL + req.url;
  // const fetch = require('node-fetch');

  fetch(url, {
    method: req.method,
    headers: Object.assign({}, req.headers, { host: 'localhost' }),
    body: req.method === 'GET' || req.method === 'HEAD' ? undefined : JSON.stringify(req.body),
  }).then(response => {
    res.status(response.status);
    response.headers.forEach((value, key) => res.setHeader(key, value));
    return response.text();
  }).then(body => res.send(body))
    .catch(e => res.status(500).send({ error: e.message }));
});

app.listen(PORT, () => {
  console.log(`ASM frontend running at http://localhost:${PORT}`);
});
