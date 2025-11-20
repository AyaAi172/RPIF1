
ADDRESS = 0x00 # TODO: Change this
from bme280 import BME280
import smbus

def main():
    bus = smbus.SMBus(1)  # Use 1 for Raspberry Pi, 0 for older models
    sensor = BME280(bus, ADDRESS)
    try:
        temperature = sensor.read_temperature()
        pressure = sensor.read_pressure()
        humidity = sensor.read_humidity()

        print(f"Temperature: {temperature} °C")
        print(f"Pressure: {pressure} hPa")
        print(f"Humidity: {humidity} %")
    except Exception as e:
        print(f"Error reading BME280 sensor: {e}")

if __name__ == "__main__":
    main()
