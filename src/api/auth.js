import axios from 'axios'

export async function loginUser(username, password) {
  const resp = await axios.post('/wp-json/jwt-auth/v1/token', {
    username,
    password,
  })
  return resp.data
}

export async function registerUser(email, password) {
  const resp = await axios.post('/wp-json/payway/v1/register', {
    username: email,
    password,
    repassword: password,
  })
  return resp.data
}
