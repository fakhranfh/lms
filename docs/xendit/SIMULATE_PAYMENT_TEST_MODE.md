> ## Documentation Index
> Fetch the complete documentation index at: https://docs.xendit.co/llms.txt
> Use this file to discover all available pages before exploring further.

# Simulate payment [test mode]

> Simulate payment completion. Test-only endpoint for payment flow validation.


## OpenAPI

````json POST /v3/payment_requests/{payment_request_id}/simulate
{
  "openapi": "3.0.0",
  "info": {
    "title": "Payments",
    "version": "3.0.0",
    "description": "Payments API enables businesses to integrate seamless and secure payment processing into their platforms, \nsupporting key features like a customizable checkout page for user-friendly payment experiences, \ndirect payments through API for tailored and flexible transaction flows, \nand recurring subscriptions to automate billing for memberships or SaaS. \nThese APIs streamline payment processing by supporting multiple payment channels, \nensuring PCI compliance, and offering features like tokenization, real-time transactions, \nand automated retries for subscription payments, making them essential for modern, scalable businesses.\n"
  },
  "servers": [
    {
      "url": "https://api.xendit.co",
      "description": "Xendit API"
    }
  ],
  "tags": [
    {
      "name": "Payment Request",
      "x-displayName": "Payment Request"
    },
    {
      "name": "Refund",
      "x-displayName": "Refund"
    },
    {
      "name": "Payment",
      "x-displayName": "Payment"
    },
    {
      "name": "Payment Token",
      "x-displayName": "Payment Token"
    },
    {
      "name": "Subscriptions",
      "description": "Manage recurring plans and cycles",
      "x-displayName": "Subscriptions"
    },
    {
      "name": "Session",
      "x-displayName": "Session"
    }
  ],
  "paths": {
    "/v3/payment_requests/{payment_request_id}/simulate": {
      "post": {
        "security": [
          {
            "Payments_API_BasicAuth": []
          }
        ],
        "operationId": "SimulatePayment",
        "summary": "Simulate payment [test mode]",
        "description": "Simulate payment completion. Test-only endpoint for payment flow validation.\n",
        "tags": [
          "Payment Request"
        ],
        "parameters": [
          {
            "$ref": "#/components/parameters/Payments_API_APIVersionHeaderExternal"
          },
          {
            "$ref": "#/components/parameters/Payments_API_PaymentRequestIdPathParam"
          }
        ],
        "requestBody": {
          "required": "true",
          "content": {
            "application/json": {
              "schema": {
                "properties": {
                  "amount": {
                    "type": "number",
                    "description": "Amount to simulate."
                  }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Simulate payment for a Payment Request",
            "content": {
              "application/json": {
                "schema": {
                  "properties": {
                    "status": {
                      "type": "string",
                      "description": "Status of a simulation will always be `PENDING`"
                    },
                    "message": {
                      "type": "string",
                      "description": "A simulated payment for the specified payment request id is being processed. You will be informed of the result via a webhook."
                    }
                  }
                }
              }
            }
          },
          "500": {
            "description": "Internal server error",
            "content": {
              "application/json": {
                "schema": {
                  "oneOf": [
                    {
                      "$ref": "#/components/schemas/Payments_API_Http500ServerError"
                    }
                  ]
                }
              }
            }
          }
        }
      }
    }
  },
  "components": {
    "parameters": {
      "Payments_API_APIVersionHeaderExternal": {
        "in": "header",
        "name": "api-version",
        "schema": {
          "example": "2024-11-11",
          "type": "string",
          "enum": [
            "2024-11-11"
          ]
        }
      },
      "Payments_API_PaymentRequestIdPathParam": {
        "in": "path",
        "name": "payment_request_id",
        "required": "true",
        "schema": {
          "type": "string",
          "minLength": "39",
          "maxLength": "39"
        },
        "example": "pr-8877c08a-740d-4153-9816-3d744ed197a5"
      }
    },
    "schemas": {
      "Payments_API_Http500ServerError": {
        "type": "object",
        "required": [
          "error_code",
          "message"
        ],
        "properties": {
          "error_code": {
            "type": "string",
            "enum": [
              "SERVER_ERROR"
            ]
          },
          "message": {
            "type": "string",
            "description": "An unexpected error occured, our team has been notified and will troubleshoot the issue"
          }
        }
      }
    },
    "securitySchemes": {
      "Payments_API_BasicAuth": {
        "type": "http",
        "scheme": "basic"
      }
    }
  }
}
````

