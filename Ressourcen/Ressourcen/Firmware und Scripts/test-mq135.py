
ADDRESS = 0x00 # TODO: Change this

from mq135 import MQ135
import smbus
def main():
    bus = smbus.SMBus(1)  # Use 1 for Raspberry Pi, 0 for older models
    sensor = MQ135(bus, ADDRESS)

    try:
        gas = sensor.read_CO2()
        print(f"CO2 concentration: {gas} ppm")
    except Exception as e:
        print(f"Error reading CO2 concentration: {e}")

if __name__ == "__main__":
    main()
